<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseReportController extends Controller
{
    // Check access to the purchase report data.
    private function authorizeReportAccess(Request $request): void
    {
        $user = $request->user();

        abort_if(!$user || !$user->is_active, 403, 'Unauthorized access.');

        if ($user->hasRole('Admin')) {
            return;
        }

        abort_unless(
            $user->hasPermission('reports.view') && $user->hasPermission('purchases.view'),
            403,
            'You do not have permission to access the Purchase Report.'
        );
    }

    // Validate the purchase report filters.
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'status' => [
                'nullable',
                Rule::in(['completed', 'received', 'partial', 'cancelled']),
            ],
        ]);

        $filters['from_date'] = $filters['from_date']
            ?? now()->startOfMonth()->toDateString();

        $filters['to_date'] = $filters['to_date']
            ?? now()->toDateString();

        return $filters;
    }

    // Build the filtered purchase report query.
    private function baseQuery(array $filters)
    {
        $query = Purchase::query()
            ->whereDate('purchase_date', '>=', $filters['from_date'])
            ->whereDate('purchase_date', '<=', $filters['to_date']);

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // Default view excludes cancelled purchases
            $query->where('status', '!=', 'cancelled');
        }

        return $query;
    }

    // Display purchase report totals and results.
    public function index(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);
        $query = $this->baseQuery($filters);

        // Active purchase summary always excludes cancelled purchases
        $activeSummaryQuery = (clone $query)->where('status', '!=', 'cancelled');

        $totalPurchases = (clone $activeSummaryQuery)->count();
        $totalAmountSum = (clone $activeSummaryQuery)->sum('total_amount');
        $totalPurchaseAmount = ((int) round((float) $totalAmountSum * 100)) / 100;

        $totalSuppliers = (clone $activeSummaryQuery)
            ->whereNotNull('supplier_id')
            ->distinct()
            ->count('supplier_id');

        $totalItemsPurchased = (int) DB::table('purchase_items')
            ->whereIn('purchase_id', (clone $activeSummaryQuery)->select('purchases.id'))
            ->sum('quantity');

        $matchingRowCount = (clone $query)->count();
        $isPrintMode = $request->boolean('print');
        $perPage = $isPrintMode ? max(1, $matchingRowCount) : 10;

        $purchases = (clone $query)
            ->with('supplier:id,name,company_name')
            ->withSum('items as total_items_qty', 'quantity')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'company_name']);

        return view('reports.purchases', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'totalPurchases' => $totalPurchases,
            'totalPurchaseAmount' => $totalPurchaseAmount,
            'totalItemsPurchased' => $totalItemsPurchased,
            'totalSuppliers' => $totalSuppliers,
            'fromDate' => $filters['from_date'],
            'toDate' => $filters['to_date'],
            'supplierId' => $filters['supplier_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'isPrintMode' => $isPrintMode,
        ]);
    }

    // Export the filtered purchase report as CSV.
    public function exportCsv(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);

        $query = $this->baseQuery($filters)
            ->with('supplier:id,name,company_name')
            ->withSum('items as total_items_qty', 'quantity')
            ->orderBy('purchase_date')
            ->orderBy('id');

        $filename = 'purchase-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Purchase No',
                'Supplier',
                'Company Name',
                'Purchase Date',
                'Total Items',
                'Purchase Total (Rs.)',
                'Status',
            ]);

            foreach ($query->cursor() as $purchase) {
                $totalCents = (int) round((float) $purchase->total_amount * 100);

                fputcsv($output, [
                    $this->sanitizeCsvCell($purchase->purchase_number),
                    $this->sanitizeCsvCell($purchase->supplier?->name ?? ''),
                    $this->sanitizeCsvCell($purchase->supplier?->company_name ?? ''),
                    Carbon::parse($purchase->purchase_date)->format('Y-m-d'),
                    (int) ($purchase->total_items_qty ?? 0),
                    number_format($totalCents / 100, 2, '.', ''),
                    ucfirst((string) $purchase->status),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // Escape a value before writing it to a CSV cell.
    private function sanitizeCsvCell(?string $value): string
    {
        $text = (string) ($value ?? '');

        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $text;
        }

        return $text;
    }
}
