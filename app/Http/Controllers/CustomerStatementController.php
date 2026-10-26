<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerStatementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerStatementController extends Controller
{
    public function __construct(
        private readonly CustomerStatementService $statementService
    ) {
    }

    /**
     * Display the Customer Statement page inside the application layout.
     */
    // Display a customer statement for the requested date range.
    public function show(Request $request, Customer $customer): View
    {
        $this->authorizeStatementAccess($request);

        [$fromDate, $toDate] = $this->validateDateFilters($request);

        $statementData = $this->statementService->buildStatement(
            $customer,
            $fromDate,
            $toDate
        );

        return view('customers.statement', $statementData);
    }

    /**
     * Display the A4 print-friendly / Save-as-PDF Customer Statement page.
     */
    // Render a printable customer statement.
    public function print(Request $request, Customer $customer): View
    {
        $this->authorizeStatementAccess($request);

        [$fromDate, $toDate] = $this->validateDateFilters($request);

        $statementData = $this->statementService->buildStatement(
            $customer,
            $fromDate,
            $toDate
        );

        return view('customers.statement-print', $statementData);
    }

    /**
     * Validate optional from_date and to_date query parameters.
     *
     * @return array{0: string|null, 1: string|null}
     */
    // Validate the optional statement date range.
    private function validateDateFilters(Request $request): array
    {
        $toDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('from_date')) {
            $toDateRules[] = 'after_or_equal:from_date';
        }

        $validated = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => $toDateRules,
        ], [
            'from_date.date_format' => 'The From Date must be in YYYY-MM-DD format.',
            'to_date.date_format' => 'The To Date must be in YYYY-MM-DD format.',
            'to_date.after_or_equal' => 'The To Date must be on or after the From Date.',
        ]);

        $fromDate = !empty($validated['from_date'])
            ? (string) $validated['from_date']
            : null;

        $toDate = !empty($validated['to_date'])
            ? (string) $validated['to_date']
            : null;

        return [$fromDate, $toDate];
    }

    /**
     * Enforce server-side authorization for Customer Statement access.
     */
    // Check access to the financial data shown on a statement.
    private function authorizeStatementAccess(Request $request): void
    {
        $user = $request->user();

        abort_if(!$user || !$user->is_active, 403, 'Unauthorized access.');

        if ($user->hasRole('Admin')) {
            return;
        }

        $hasAccess = $user->hasPermission('customers.view')
            && $user->hasPermission('invoices.view')
            && $user->hasPermission('payments.view');

        abort_unless(
            $hasAccess,
            403,
            'You do not have permission to view customer statements.'
        );
    }
}
