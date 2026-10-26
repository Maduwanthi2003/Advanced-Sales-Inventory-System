<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // List payments with optional filters.
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['invoice.customer', 'user']);

        if ($request->filled('payment_method')) {
            $query->where(
                'payment_method',
                $request->payment_method
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate(
                'payment_date',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'payment_date',
                '<=',
                $request->to_date
            );
        }

        $baseQuery = clone $query;

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($invoice) use ($search) {
                        $invoice->where(
                            'invoice_number',
                            'like',
                            "%{$search}%"
                        )->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('name', 'like', "%{$search}%");
                        });
                    });
            });
        }

        $payments = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->filled('search')) {
            $payments->getCollection()->each(function ($payment) use ($baseQuery) {
                $payment->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '>', $payment->id)->count() + 1
                );
            });
        }

        return view('payments.index', compact('payments'));
    }

    // Show the payment form and eligible invoices.
    public function create(Request $request)
    {
        $invoices = Invoice::query()
            ->where('status', 'active')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->with('customer')
            ->withSum([
                'payments as paid_amount' => function ($query) {
                    $query->where('status', 'completed');
                }
            ], 'amount')
            ->orderByDesc('id')
            ->get()
            ->filter(function ($invoice) {
                $total = (int) round($invoice->grand_total * 100);
                $paid = (int) round(($invoice->paid_amount ?? 0) * 100);

                return $total > $paid;
            })
            ->values();

        $selectedInvoiceId = $request->query('invoice_id');

        return view('payments.create', compact(
            'invoices',
            'selectedInvoiceId'
        ));
    }

    // Validate and record a payment against an invoice.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id'
            ],
            'payment_date' => ['required', 'date'],
            'payment_method' => [
                'required',
                'in:cash,bank_transfer,cheque,online_payment'
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999999999999.99',
                'decimal:0,2'
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000'
            ],
        ]);

        DB::transaction(function () use ($validated, $request) {

            $invoice = Invoice::whereKey($validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($invoice->status !== 'active') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Payments are not allowed for cancelled invoices.',
                ]);
            }

            if ($invoice->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice is already fully paid.',
                ]);
            }

            // Integer cents avoid floating-point balance errors.
            $totalCents = (int) round(
                (float) $invoice->grand_total * 100
            );

            $paidCents = (int) round(
                (float) $invoice->payments()
                    ->where('status', 'completed')
                    ->sum('amount') * 100
            );

            $amountCents = (int) round(
                (float) $validated['amount'] * 100
            );

            $balanceCents = $totalCents - $paidCents;

            if ($balanceCents <= 0) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice has no outstanding balance.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds the outstanding balance of Rs. '
                        . number_format($balanceCents / 100, 2),
                ]);
            }

            $payment = Payment::create([
                'payment_number' => null,
                'invoice_id' => $invoice->id,
                'user_id' => $request->user()->id,
                'payment_date' => $validated['payment_date'],
                'amount' => number_format(
                    $amountCents / 100,
                    2,
                    '.',
                    ''
                ),
                'payment_method' => $validated['payment_method'],
                'reference_number' =>
                    $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
            ]);

            $payment->update([
                'payment_number' => sprintf(
                    'PAY-%04d-%03d',
                    (int) date('Y', strtotime($validated['payment_date'])),
                    $payment->id
                ),
            ]);

            $newPaidCents = $paidCents + $amountCents;

            $invoice->update([
                'payment_status' => $newPaidCents >= $totalCents
                    ? 'paid'
                    : 'partial',
            ]);
        }, 3);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    // Show a payment and its invoice details.
    public function show(Payment $payment)
    {
        $payment->load([
            'invoice.customer',
            'invoice.payments',
            'user'
        ]);

        return view('payments.show', compact('payment'));
    }

    // Reverse a payment when the request is authorized.
    public function reverse(Request $request, Payment $payment)
    {
        Gate::authorize('reverse-payments');

        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($payment, $validated, $request) {

            // Always lock invoice first, then payment.
            $invoice = Invoice::whereKey($payment->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== 'completed') {
                throw ValidationException::withMessages([
                    'reversal_reason' =>
                        'Only completed payments can be reversed.',
                ]);
            }

            $lockedPayment->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $request->user()->id,
                'reversal_reason' => $validated['reversal_reason'],
            ]);

            // Only completed payments count towards the invoice balance.
            $paidCents = (int) round(
                (float) $invoice->payments()
                    ->where('status', 'completed')
                    ->sum('amount') * 100
            );

            $totalCents = (int) round(
                (float) $invoice->grand_total * 100
            );

            if ($paidCents > $totalCents) {
                throw ValidationException::withMessages([
                    'reversal_reason' =>
                        'Payment totals exceed the invoice total. Review required.',
                ]);
            }

            $newStatus = $paidCents <= 0
                ? 'unpaid'
                : ($paidCents >= $totalCents ? 'paid' : 'partial');

            $invoice->update([
                'payment_status' => $newStatus,
            ]);

        }, 3);

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment reversed successfully.');
    }

    // Render a printable payment receipt.
    public function receipt(Payment $payment)
    {
        $payment->load([
            'invoice.customer',
            'invoice.user',
            'user',
        ]);

        return view('payments.receipt', compact('payment'));
    }
}
