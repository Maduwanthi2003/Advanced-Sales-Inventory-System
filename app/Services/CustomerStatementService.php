<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;

/**
 * Service for generating chronological Customer Statements.
 *
 * Accounting Note:
 * Historical statements are calculated from current transaction statuses
 * (`invoices.status = 'active'` and `payments.status = 'completed'`).
 * Cancelled invoices and reversed payments are excluded from all receivable
 * balance calculations and are surfaced separately in the non-financial
 * audit trail section for transparency.
 */
class CustomerStatementService
{
    /**
     * Build the complete statement data structure for a customer.
     *
     * @param  Customer     $customer
     * @param  string|null  $fromDate  Validated YYYY-MM-DD or null
     * @param  string|null  $toDate    Validated YYYY-MM-DD or null
     * @return array<string, mixed>
     */
    public function buildStatement(
        Customer $customer,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        // 1. Opening Balance (only transactions strictly before $fromDate)
        $openingInvoicesCents = 0;
        $openingPaymentsCents = 0;

        if ($fromDate !== null) {
            $openingInvoicesSum = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->whereDate('invoice_date', '<', $fromDate)
                ->sum('grand_total');

            $openingPaymentsSum = Payment::query()
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->where('invoices.customer_id', $customer->id)
                ->where('invoices.status', 'active')
                ->where('payments.status', 'completed')
                ->whereDate('payments.payment_date', '<', $fromDate)
                ->sum('payments.amount');

            $openingInvoicesCents = $this->toCents($openingInvoicesSum);
            $openingPaymentsCents = $this->toCents($openingPaymentsSum);
        }

        $openingBalanceCents = $openingInvoicesCents - $openingPaymentsCents;

        // 2. Period Active Invoices (filtered by invoice_date)
        $invoicesQuery = Invoice::query()
            ->select([
                'id',
                'invoice_number',
                'customer_id',
                'invoice_date',
                'due_date',
                'grand_total',
                'payment_status',
                'created_at',
            ])
            ->where('customer_id', $customer->id)
            ->where('status', 'active');

        if ($fromDate !== null) {
            $invoicesQuery->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $invoicesQuery->whereDate('invoice_date', '<=', $toDate);
        }

        $periodInvoices = $invoicesQuery->get();

        // 3. Period Completed Payments on Active Invoices (filtered by payment_date)
        $paymentsQuery = Payment::query()
            ->select([
                'payments.id',
                'payments.payment_number',
                'payments.invoice_id',
                'payments.payment_date',
                'payments.amount',
                'payments.payment_method',
                'payments.reference_number',
                'payments.created_at',
                'invoices.invoice_number as invoice_number',
            ])
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.customer_id', $customer->id)
            ->where('invoices.status', 'active')
            ->where('payments.status', 'completed');

        if ($fromDate !== null) {
            $paymentsQuery->whereDate('payments.payment_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $paymentsQuery->whereDate('payments.payment_date', '<=', $toDate);
        }

        $periodPayments = $paymentsQuery->get();

        // 4. Build and deterministically order chronological ledger entries
        $entries = [];

        foreach ($periodInvoices as $invoice) {
            $debitCents = $this->toCents($invoice->grand_total);
            $dueNote = $invoice->due_date
                ? ' (Due: ' . $invoice->due_date->format('Y-m-d') . ')'
                : '';

            $entries[] = [
                'date' => $invoice->invoice_date->format('Y-m-d'),
                'type' => 'invoice',
                'type_label' => 'Invoice',
                'type_priority' => 1,
                'created_timestamp' => $invoice->created_at?->getTimestamp() ?? 0,
                'id' => (int) $invoice->id,
                'reference' => $invoice->invoice_number,
                'route_name' => 'invoices.show',
                'route_param' => (int) $invoice->id,
                'description' => 'Sales Invoice' . $dueNote,
                'debit_cents' => $debitCents,
                'credit_cents' => 0,
            ];
        }

        foreach ($periodPayments as $payment) {
            $creditCents = $this->toCents($payment->amount);
            $methodLabel = ucwords(str_replace('_', ' ', (string) $payment->payment_method));
            $refNote = $payment->reference_number
                ? ' — Ref: ' . $payment->reference_number
                : '';

            $entries[] = [
                'date' => $payment->payment_date->format('Y-m-d'),
                'type' => 'payment',
                'type_label' => 'Payment',
                'type_priority' => 2,
                'created_timestamp' => $payment->created_at?->getTimestamp() ?? 0,
                'id' => (int) $payment->id,
                'reference' => $payment->payment_number ?: ('PAY-#' . $payment->id),
                'route_name' => 'payments.show',
                'route_param' => (int) $payment->id,
                'description' => 'Payment for ' . $payment->invoice_number . ' (' . $methodLabel . ')' . $refNote,
                'debit_cents' => 0,
                'credit_cents' => $creditCents,
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            return [
                $a['date'],
                $a['type_priority'],
                $a['created_timestamp'],
                $a['id'],
            ] <=> [
                $b['date'],
                $b['type_priority'],
                $b['created_timestamp'],
                $b['id'],
            ];
        });

        // 5. Calculate Running Balances and Period Totals in integer cents
        $runningBalanceCents = $openingBalanceCents;
        $periodInvoicedCents = 0;
        $periodPaidCents = 0;

        foreach ($entries as &$entry) {
            $periodInvoicedCents += $entry['debit_cents'];
            $periodPaidCents += $entry['credit_cents'];
            $runningBalanceCents = $runningBalanceCents + $entry['debit_cents'] - $entry['credit_cents'];

            $entry['debit'] = $entry['debit_cents'] / 100;
            $entry['credit'] = $entry['credit_cents'] / 100;
            $entry['running_balance_cents'] = $runningBalanceCents;
            $entry['running_balance'] = $runningBalanceCents / 100;
        }
        unset($entry);

        $closingBalanceCents = $openingBalanceCents + $periodInvoicedCents - $periodPaidCents;

        // 6. Audit Section: Cancelled Invoices & Reversed Payments (excluded from financial totals)
        $cancelledInvoicesQuery = Invoice::query()
            ->select([
                'id',
                'invoice_number',
                'invoice_date',
                'grand_total',
                'cancelled_at',
                'notes',
            ])
            ->where('customer_id', $customer->id)
            ->where('status', 'cancelled');

        if ($fromDate !== null) {
            $cancelledInvoicesQuery->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $cancelledInvoicesQuery->whereDate('invoice_date', '<=', $toDate);
        }

        $cancelledInvoices = $cancelledInvoicesQuery
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $reversedPaymentsQuery = Payment::query()
            ->select([
                'payments.id',
                'payments.payment_number',
                'payments.invoice_id',
                'payments.payment_date',
                'payments.amount',
                'payments.payment_method',
                'payments.reversed_at',
                'payments.reversal_reason',
                'invoices.invoice_number as invoice_number',
            ])
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.customer_id', $customer->id)
            ->where('payments.status', 'reversed');

        if ($fromDate !== null) {
            $reversedPaymentsQuery->whereDate('payments.payment_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $reversedPaymentsQuery->whereDate('payments.payment_date', '<=', $toDate);
        }

        $reversedPayments = $reversedPaymentsQuery
            ->orderBy('payments.payment_date')
            ->orderBy('payments.id')
            ->get();

        $auditEntries = [];

        foreach ($cancelledInvoices as $cancelledInvoice) {
            $cancelledAtText = $cancelledInvoice->cancelled_at
                ? 'Cancelled on ' . $cancelledInvoice->cancelled_at->format('Y-m-d H:i')
                : 'Cancelled invoice';

            $auditEntries[] = [
                'date' => $cancelledInvoice->invoice_date->format('Y-m-d'),
                'type' => 'cancelled_invoice',
                'status_label' => 'Cancelled Invoice',
                'reference' => $cancelledInvoice->invoice_number,
                'route_name' => 'invoices.show',
                'route_param' => (int) $cancelledInvoice->id,
                'details' => $cancelledAtText,
                'amount_cents' => $this->toCents($cancelledInvoice->grand_total),
                'amount' => $this->toCents($cancelledInvoice->grand_total) / 100,
                'id' => (int) $cancelledInvoice->id,
            ];
        }

        foreach ($reversedPayments as $reversedPayment) {
            $reversedAtText = $reversedPayment->reversed_at
                ? 'Reversed on ' . $reversedPayment->reversed_at->format('Y-m-d H:i')
                : 'Reversed payment';
            $reasonText = $reversedPayment->reversal_reason
                ? ' — Reason: ' . $reversedPayment->reversal_reason
                : '';

            $auditEntries[] = [
                'date' => $reversedPayment->payment_date->format('Y-m-d'),
                'type' => 'reversed_payment',
                'status_label' => 'Reversed Payment',
                'reference' => $reversedPayment->payment_number ?: ('PAY-#' . $reversedPayment->id),
                'route_name' => 'payments.show',
                'route_param' => (int) $reversedPayment->id,
                'details' => 'For ' . $reversedPayment->invoice_number . ' (' . $reversedAtText . ')' . $reasonText,
                'amount_cents' => $this->toCents($reversedPayment->amount),
                'amount' => $this->toCents($reversedPayment->amount) / 100,
                'id' => (int) $reversedPayment->id,
            ];
        }

        usort($auditEntries, static function (array $a, array $b): int {
            return [$a['date'], $a['type'], $a['id']] <=> [$b['date'], $b['type'], $b['id']];
        });

        // 7. Human-readable Statement Period label
        $periodLabel = $this->formatPeriodLabel($fromDate, $toDate);

        return [
            'customer' => $customer,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'periodLabel' => $periodLabel,
            'generatedAt' => Carbon::now(config('app.timezone', 'UTC')),
            'openingBalanceCents' => $openingBalanceCents,
            'openingBalance' => $openingBalanceCents / 100,
            'periodInvoicedCents' => $periodInvoicedCents,
            'periodInvoiced' => $periodInvoicedCents / 100,
            'periodPaidCents' => $periodPaidCents,
            'periodPaid' => $periodPaidCents / 100,
            'closingBalanceCents' => $closingBalanceCents,
            'closingBalance' => $closingBalanceCents / 100,
            'entries' => $entries,
            'auditEntries' => $auditEntries,
        ];
    }

    /**
     * Convert a decimal/float/string monetary value to integer cents safely.
     */
    public function toCents(float|int|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round((float) $amount * 100);
    }

    /**
     * Build a human-readable label for the selected statement period.
     */
    private function formatPeriodLabel(?string $fromDate, ?string $toDate): string
    {
        if ($fromDate !== null && $toDate !== null) {
            return Carbon::parse($fromDate)->format('d M Y')
                . ' to '
                . Carbon::parse($toDate)->format('d M Y');
        }

        if ($fromDate !== null) {
            return 'From ' . Carbon::parse($fromDate)->format('d M Y') . ' onwards';
        }

        if ($toDate !== null) {
            return 'Up to ' . Carbon::parse($toDate)->format('d M Y');
        }

        return 'All Transactions (Up to '
            . Carbon::now(config('app.timezone', 'UTC'))->format('d M Y')
            . ')';
    }
}
