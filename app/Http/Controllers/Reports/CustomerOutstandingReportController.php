<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerOutstandingReportController extends Controller
{
    // Check access to the customer outstanding report data.
    private function authorizeReportAccess(Request $request): void
    {
        $user = $request->user();

        abort_if(!$user || !$user->is_active, 403, 'Unauthorized access.');

        if ($user->hasRole('Admin')) {
            return;
        }

        $hasAccess = $user->hasPermission('reports.view')
            && $user->hasPermission('customers.view')
            && $user->hasPermission('invoices.view')
            && $user->hasPermission('payments.view');

        abort_unless(
            $hasAccess,
            403,
            'You do not have permission to access the Customer Outstanding Report.'
        );
    }

    // Validate the customer outstanding report filters.
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'balance_status' => [
                'nullable',
                Rule::in(['outstanding', 'settled']),
            ],
        ]);
    }

    /**
     * Build the base query using separate correlated subqueries for invoices
     * and payments so invoice totals are never double-counted when an invoice
     * has multiple payment records.
     */
    // Build the filtered customer outstanding query.
    private function baseQuery(array $filters)
    {
        $activeInvoicesCountSub = DB::table('invoices')
            ->selectRaw('COUNT(*)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active');

        $unpaidCountSub = DB::table('invoices')
            ->selectRaw('COUNT(*)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active')
            ->where('invoices.payment_status', 'unpaid');

        $partialCountSub = DB::table('invoices')
            ->selectRaw('COUNT(*)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active')
            ->where('invoices.payment_status', 'partial');

        $paidCountSub = DB::table('invoices')
            ->selectRaw('COUNT(*)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active')
            ->where('invoices.payment_status', 'paid');

        $totalInvoicedSub = DB::table('invoices')
            ->selectRaw('COALESCE(SUM(invoices.grand_total), 0)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active');

        $completedPaidSub = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->selectRaw('COALESCE(SUM(payments.amount), 0)')
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->where('invoices.status', 'active')
            ->where('payments.status', 'completed');

        $query = Customer::query()
            ->select([
                'customers.id',
                'customers.name',
                'customers.contact_person',
                'customers.phone',
                'customers.email',
                'customers.is_active',
            ])
            ->selectSub($activeInvoicesCountSub, 'active_invoices_count')
            ->selectSub($unpaidCountSub, 'unpaid_invoices_count')
            ->selectSub($partialCountSub, 'partial_invoices_count')
            ->selectSub($paidCountSub, 'paid_invoices_count')
            ->selectSub($totalInvoicedSub, 'total_invoiced_amount')
            ->selectSub($completedPaidSub, 'completed_paid_amount');

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('customers.name', 'like', "%{$search}%")
                    ->orWhere('customers.contact_person', 'like', "%{$search}%")
                    ->orWhere('customers.phone', 'like', "%{$search}%")
                    ->orWhere('customers.email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customers.id', $filters['customer_id']);
        }

        if (!empty($filters['balance_status'])) {
            if ($filters['balance_status'] === 'outstanding') {
                $query->havingRaw('ROUND(total_invoiced_amount * 100) > ROUND(completed_paid_amount * 100)');
            } elseif ($filters['balance_status'] === 'settled') {
                $query->havingRaw('ROUND(total_invoiced_amount * 100) <= ROUND(completed_paid_amount * 100)');
            }
        }

        return $query;
    }

    // Add the calculated balance details used by the report.
    private function enrichCustomerRow(Customer $customer): Customer
    {
        $invoicedCents = (int) round((float) ($customer->total_invoiced_amount ?? 0) * 100);
        $paidCents = (int) round((float) ($customer->completed_paid_amount ?? 0) * 100);
        $netClosingCents = $invoicedCents - $paidCents;
        $outstandingCents = max(0, $netClosingCents);
        $overpaymentCents = $netClosingCents < 0 ? abs($netClosingCents) : 0;

        $activeCount = (int) ($customer->active_invoices_count ?? 0);
        $unpaidCount = (int) ($customer->unpaid_invoices_count ?? 0);
        $partialCount = (int) ($customer->partial_invoices_count ?? 0);
        $paidCount = (int) ($customer->paid_invoices_count ?? 0);

        if ($activeCount === 0) {
            $overallStatus = 'no_invoices';
            $overallLabel = 'No Invoices';
        } elseif ($overpaymentCents > 0) {
            $overallStatus = 'overpaid';
            $overallLabel = 'Overpaid Anomaly';
        } elseif ($outstandingCents === 0) {
            $overallStatus = 'paid';
            $overallLabel = 'Settled';
        } elseif ($paidCents > 0) {
            $overallStatus = 'partial';
            $overallLabel = 'Partially Paid';
        } else {
            $overallStatus = 'unpaid';
            $overallLabel = 'Unpaid';
        }

        $customer->setAttribute('invoiced_cents', $invoicedCents);
        $customer->setAttribute('paid_cents', $paidCents);
        $customer->setAttribute('outstanding_cents', $outstandingCents);
        $customer->setAttribute('net_closing_cents', $netClosingCents);
        $customer->setAttribute('overpayment_cents', $overpaymentCents);
        $customer->setAttribute('total_invoiced', $invoicedCents / 100);
        $customer->setAttribute('completed_paid', $paidCents / 100);
        $customer->setAttribute('outstanding_balance', $outstandingCents / 100);
        $customer->setAttribute('overpayment_amount', $overpaymentCents / 100);
        $customer->setAttribute('overall_status', $overallStatus);
        $customer->setAttribute('overall_label', $overallLabel);
        $customer->setAttribute(
            'status_breakdown',
            "Unpaid: {$unpaidCount} | Partial: {$partialCount} | Paid: {$paidCount}"
        );

        return $customer;
    }

    // Display customer outstanding totals and results.
    public function index(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);
        $query = $this->baseQuery($filters);

        // Compute full-dataset summary totals across all matching customers
        $allMatching = (clone $query)->orderBy('customers.name')->get();

        $totalCustomersCount = $allMatching->count();
        $customersWithBalanceCount = 0;
        $totalInvoicedCents = 0;
        $totalPaidCents = 0;
        $totalOutstandingCents = 0;
        $anomalyCount = 0;

        foreach ($allMatching as $row) {
            $this->enrichCustomerRow($row);
            $totalInvoicedCents += $row->invoiced_cents;
            $totalPaidCents += $row->paid_cents;
            $totalOutstandingCents += $row->outstanding_cents;

            if ($row->outstanding_cents > 0) {
                $customersWithBalanceCount++;
            }
            if ($row->overpayment_cents > 0) {
                $anomalyCount++;
            }
        }

        $isPrintMode = $request->boolean('print');
        $perPage = $isPrintMode ? max(1, $totalCustomersCount) : 10;

        $paginated = (clone $query)
            ->orderByDesc(DB::raw('ROUND(total_invoiced_amount * 100) - ROUND(completed_paid_amount * 100)'))
            ->orderBy('customers.name')
            ->paginate($perPage)
            ->withQueryString();

        $paginated->getCollection()->transform(
            fn (Customer $customer) => $this->enrichCustomerRow($customer)
        );

        $customerOptions = Customer::orderBy('name')->get(['id', 'name']);

        return view('reports.customer-outstanding', [
            'customersReport' => $paginated,
            'customerOptions' => $customerOptions,
            'totalCustomersCount' => $totalCustomersCount,
            'customersWithBalanceCount' => $customersWithBalanceCount,
            'totalInvoiced' => $totalInvoicedCents / 100,
            'totalPaid' => $totalPaidCents / 100,
            'totalOutstanding' => $totalOutstandingCents / 100,
            'anomalyCount' => $anomalyCount,
            'search' => $filters['search'] ?? '',
            'customerId' => $filters['customer_id'] ?? null,
            'balanceStatus' => $filters['balance_status'] ?? null,
            'isPrintMode' => $isPrintMode,
        ]);
    }

    // Export the filtered outstanding report as CSV.
    public function exportCsv(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->validatedFilters($request);

        $rows = $this->baseQuery($filters)
            ->orderByDesc(DB::raw('ROUND(total_invoiced_amount * 100) - ROUND(completed_paid_amount * 100)'))
            ->orderBy('customers.name')
            ->get();

        $filename = 'customer-outstanding-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Customer Name',
                'Contact Person',
                'Phone',
                'Email',
                'Active Invoices',
                'Total Invoiced (Rs.)',
                'Completed Payments (Rs.)',
                'Outstanding Balance (Rs.)',
                'Payment Status Summary',
                'Account Status',
            ]);

            foreach ($rows as $customer) {
                $this->enrichCustomerRow($customer);

                fputcsv($output, [
                    $this->sanitizeCsvCell($customer->name),
                    $this->sanitizeCsvCell($customer->contact_person ?? ''),
                    $this->sanitizeCsvCell($customer->phone ?? ''),
                    $this->sanitizeCsvCell($customer->email ?? ''),
                    (int) $customer->active_invoices_count,
                    number_format($customer->total_invoiced, 2, '.', ''),
                    number_format($customer->completed_paid, 2, '.', ''),
                    number_format($customer->outstanding_balance, 2, '.', ''),
                    $customer->status_breakdown,
                    $customer->overall_label,
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
