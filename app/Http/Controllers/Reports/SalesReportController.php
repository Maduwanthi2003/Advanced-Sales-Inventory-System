<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesReportController extends Controller
{
    // Check access to the sales report data.
    private function authorizeReportAccess(Request $request): void
    {
        $user = $request->user();

        abort_if(!$user || !$user->is_active, 403, 'Unauthorized access.');

        if ($user->hasRole('Admin')) {
            return;
        }

        abort_unless(
            $user->hasPermission('reports.view') && $user->hasPermission('invoices.view'),
            403,
            'You do not have permission to access the Sales Report.'
        );
    }

    // Build the filtered sales report query.
    private function reportQuery(array $filters)
    {
        $query = Invoice::query()
            ->where('status', 'active')
            ->whereDate('invoice_date', '>=', $filters['from_date'])
            ->whereDate('invoice_date', '<=', $filters['to_date']);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['product_id'])) {
            $productId = $filters['product_id'];

            $query->whereExists(function ($q) use ($productId) {
                $q->selectRaw('1')
                    ->from('invoice_items')
                    ->whereColumn('invoice_items.invoice_id', 'invoices.id')
                    ->where('invoice_items.product_id', $productId);
            });
        }

        return $query;
    }

    // Validate the sales report filters.
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'payment_status' => [
                'nullable',
                Rule::in(['paid', 'partial', 'unpaid']),
            ],
        ]);

        $filters['from_date'] = $filters['from_date']
            ?? now()->startOfMonth()->toDateString();

        $filters['to_date'] = $filters['to_date']
            ?? now()->toDateString();

        return $filters;
    }

    // Display sales report totals and results.
    public function index(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);
        $query = $this->reportQuery($filters);

        // Calculate full-dataset financial summary across all matching active invoices
        // Per-invoice outstanding = max(0, grand_total_cents - completed_paid_cents)
        $summaryInvoices = (clone $query)
            ->withSum([
                'payments as completed_paid_amount' => function ($q) {
                    $q->where('status', 'completed');
                },
            ], 'amount')
            ->get(['id', 'customer_id', 'grand_total']);

        $totalInvoices = $summaryInvoices->count();
        $totalSalesCents = 0;
        $totalPaidCents = 0;
        $totalOutstandingCents = 0;

        foreach ($summaryInvoices as $inv) {
            $invTotalCents = (int) round((float) $inv->grand_total * 100);
            $invPaidCents = (int) round((float) ($inv->completed_paid_amount ?? 0) * 100);
            $invOutstandingCents = max(0, $invTotalCents - $invPaidCents);

            $totalSalesCents += $invTotalCents;
            $totalPaidCents += $invPaidCents;
            $totalOutstandingCents += $invOutstandingCents;
        }

        $totalSales = $totalSalesCents / 100;
        $totalPaid = $totalPaidCents / 100;
        $totalOutstanding = $totalOutstandingCents / 100;

        $totalCustomers = $summaryInvoices
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->count();

        $totalItemsSold = (int) DB::table('invoice_items')
            ->whereIn('invoice_id', (clone $query)->select('invoices.id'))
            ->when(
                $filters['product_id'] ?? null,
                fn ($q, $id) => $q->where('product_id', $id)
            )
            ->sum('quantity');

        $year = Carbon::parse($filters['from_date'])->year;

        $monthlySales = [];

        foreach ([$year, $year - 1] as $chartYear) {
            $chartFilters = $filters;
            $chartFilters['from_date'] = "$chartYear-01-01";
            $chartFilters['to_date'] = "$chartYear-12-31";

            $monthlySales[$chartYear] = $this
                ->reportQuery($chartFilters)
                ->selectRaw('MONTH(invoice_date) AS month_number')
                ->selectRaw('SUM(grand_total) AS total')
                ->groupByRaw('MONTH(invoice_date)')
                ->pluck('total', 'month_number')
                ->all();
        }

        $topProducts = DB::table('invoice_items')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereIn(
                'invoice_items.invoice_id',
                (clone $query)->select('invoices.id')
            )
            ->when(
                $filters['product_id'] ?? null,
                fn ($q, $id) => $q->where('products.id', $id)
            )
            ->select('products.id', 'products.name')
            ->selectRaw('SUM(invoice_items.total) AS total_sales')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $isPrintMode = $request->boolean('print');
        $perPage = $isPrintMode ? max(1, $totalInvoices) : 10;

        $invoices = (clone $query)
            ->with(['customer:id,name', 'items:id,invoice_id,quantity'])
            ->withSum([
                'payments as completed_paid_amount' => function ($q) {
                    $q->where('status', 'completed');
                },
            ], 'amount')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $invoices->getCollection()->transform(function (Invoice $invoice) {
            $invTotalCents = (int) round((float) $invoice->grand_total * 100);
            $invPaidCents = (int) round((float) ($invoice->completed_paid_amount ?? 0) * 100);
            $invOutstandingCents = max(0, $invTotalCents - $invPaidCents);

            $invoice->setAttribute('completed_paid', $invPaidCents / 100);
            $invoice->setAttribute('outstanding_balance', $invOutstandingCents / 100);

            return $invoice;
        });

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('reports.sales', [
            'invoices' => $invoices,
            'customers' => $customers,
            'products' => $products,
            'totalInvoices' => $totalInvoices,
            'totalSales' => $totalSales,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'totalCustomers' => $totalCustomers,
            'totalItemsSold' => $totalItemsSold,
            'monthlySales' => $monthlySales,
            'topProducts' => $topProducts,
            'fromDate' => $filters['from_date'],
            'toDate' => $filters['to_date'],
            'customerId' => $filters['customer_id'] ?? null,
            'productId' => $filters['product_id'] ?? null,
            'paymentStatus' => $filters['payment_status'] ?? null,
            'year' => $year,
            'isPrintMode' => $isPrintMode,
        ]);
    }

    // Export the filtered sales report as CSV.
    public function exportCsv(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);

        $query = $this->reportQuery($filters)
            ->with(['customer:id,name', 'items:id,invoice_id,quantity'])
            ->withSum([
                'payments as completed_paid_amount' => function ($q) {
                    $q->where('status', 'completed');
                },
            ], 'amount')
            ->orderBy('invoice_date')
            ->orderBy('id');

        $filename = 'sales-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Invoice No',
                'Customer',
                'Date',
                'Total Items',
                'Total Amount (Rs.)',
                'Completed Payments (Rs.)',
                'Outstanding Balance (Rs.)',
                'Payment Status',
            ]);

            foreach ($query->cursor() as $invoice) {
                $invTotalCents = (int) round((float) $invoice->grand_total * 100);
                $invPaidCents = (int) round((float) ($invoice->completed_paid_amount ?? 0) * 100);
                $invOutstandingCents = max(0, $invTotalCents - $invPaidCents);

                fputcsv($output, [
                    $this->sanitizeCsvCell($invoice->invoice_number),
                    $this->sanitizeCsvCell($invoice->customer?->name ?? ''),
                    Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                    $invoice->items->sum('quantity'),
                    number_format($invTotalCents / 100, 2, '.', ''),
                    number_format($invPaidCents / 100, 2, '.', ''),
                    number_format($invOutstandingCents / 100, 2, '.', ''),
                    ucfirst((string) $invoice->payment_status),
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
