
{{-- Payment entry form and invoice summary. --}}
@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')
<div class="pay-page">

    @if($errors->any())
        <div class="pay-alert pay-alert-error" role="alert">
            <strong>Please correct these errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('payments.store') }}"
          method="POST"
          id="paymentForm">

        @csrf

        <div class="pay-card pay-form-card">
            <div class="pay-section-title">
                <h2>Payment Information</h2>
                <p>Enter payment details below.</p>
            </div>

            <div class="pay-form-grid">

                <div class="pay-form-column">
                    <div class="pay-field">
                        <label>Payment No <span>*</span></label>
                        <div class="pay-input-icon pay-readonly">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3h9l4 4v14H6z"/>
                                    <path d="M15 3v5h4M9 12h7M9 16h7"/>
                                </svg>
                            </span>
                            <input type="text"
                                   value="Auto-generated when saved"
                                   readonly>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="invoice_id">
                            Invoice No <span>*</span>
                        </label>

                        <div class="pay-input-icon">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3h9l4 4v14H6z"/>
                                    <path d="M15 3v5h4M9 12h7M9 16h7"/>
                                </svg>
                            </span>

                            <select id="invoice_id"
                                    name="invoice_id"
                                    required>
                                <option value="">Select Invoice</option>

                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}"
                                        @selected(
                                            old(
                                                'invoice_id',
                                                $selectedInvoiceId
                                            ) == $invoice->id
                                        )>
                                        {{ $invoice->invoice_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="paymentCustomer">Customer</label>
                        <div class="pay-input-icon pay-readonly">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                                </svg>
                            </span>
                            <input id="paymentCustomer"
                                   type="text"
                                   placeholder="Select an invoice"
                                   readonly>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="paymentInvoiceDate">Invoice Date</label>
                        <div class="pay-input-icon pay-readonly">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M7 3v4M17 3v4M3 10h18"/>
                                </svg>
                            </span>
                            <input id="paymentInvoiceDate"
                                   type="text"
                                   placeholder="—"
                                   readonly>
                        </div>
                    </div>
                </div>

                <div class="pay-form-column">
                    <div class="pay-field">
                        <label for="payment_date">
                            Payment Date <span>*</span>
                        </label>

                        <div class="pay-input-icon">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M7 3v4M17 3v4M3 10h18"/>
                                </svg>
                            </span>
                            <input id="payment_date"
                                   type="date"
                                   name="payment_date"
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                   required>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="payment_method">
                            Payment Method <span>*</span>
                        </label>

                        <div class="pay-input-icon">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                                    <path d="M2 10h20M6 15h4"/>
                                </svg>
                            </span>
                            <select id="payment_method"
                                    name="payment_method"
                                    required>
                                <option value="">Select Method</option>
                                <option value="cash"
                                    @selected(old('payment_method') === 'cash')>
                                    Cash
                                </option>
                                <option value="bank_transfer"
                                    @selected(old('payment_method') === 'bank_transfer')>
                                    Bank Transfer
                                </option>
                                <option value="cheque"
                                    @selected(old('payment_method') === 'cheque')>
                                    Cheque
                                </option>
                                <option value="online_payment"
                                    @selected(old('payment_method') === 'online_payment')>
                                    Online Payment
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="payment_amount">
                            Amount (Rs.) <span>*</span>
                        </label>

                        <div class="pay-input-icon">
                            <span class="pay-currency-prefix" aria-hidden="true">Rs.</span>
                            <input id="payment_amount"
                                   name="amount"
                                   type="number"
                                   min="0.01"
                                   max="9999999999999.99"
                                   step="0.01"
                                   value="{{ old('amount') }}"
                                   placeholder="0.00"
                                   required>
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="reference_number">
                            Reference No (Optional)
                        </label>

                        <div class="pay-input-icon">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3h9l4 4v14H6z"/>
                                    <path d="M15 3v5h4M9 12h7M9 16h7"/>
                                </svg>
                            </span>
                            <input id="reference_number"
                                   name="reference_number"
                                   type="text"
                                   maxlength="100"
                                   value="{{ old('reference_number') }}"
                                   placeholder="Transaction ID, cheque number...">
                        </div>
                    </div>

                    <div class="pay-field">
                        <label for="payment_notes">
                            Remarks (Optional)
                        </label>

                        <textarea id="payment_notes"
                                  name="notes"
                                  rows="2"
                                  placeholder="Enter payment remarks (optional)">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="pay-card pay-summary-card">
            <div class="pay-section-title">
                <h2>Invoice Details</h2>
                <p>Invoice information and payment summary.</p>
            </div>

            <div class="pay-table-wrap">
                <table class="pay-table pay-summary-table">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th>Total Amount (Rs.)</th>
                            <th>Paid Amount (Rs.)</th>
                            <th>Balance (Rs.)</th>
                            <th>Payment Amount (Rs.)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td id="summaryInvoice">—</td>
                            <td id="summaryCustomer">—</td>
                            <td id="summaryDate">—</td>
                            <td id="summaryTotal">0.00</td>
                            <td id="summaryPaid">0.00</td>
                            <td id="summaryBalance">0.00</td>
                            <td>
                                <span id="summaryPayment">0.00</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p id="paymentBalanceMessage"
               class="pay-balance-message"></p>

            <div class="pay-form-actions">
                <a href="{{ route('payments.index') }}"
                   class="pay-btn pay-btn-outline">
                    Cancel
                </a>

                <button type="submit"
                        id="savePaymentButton"
                        class="pay-btn pay-btn-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 4h11l3 3v13H5z"/>
                        <path d="M8 4v6h8V4M8 20v-6h8v6"/>
                    </svg>
                    Save Payment
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payments.css') }}?v=11">
@endpush

@push('scripts')
<script>
window.paymentInvoiceData = {!! json_encode(
    $invoices->map(fn ($invoice) => [
        'id' => $invoice->id,
        'invoice_number' => $invoice->invoice_number,
        'customer' => $invoice->customer?->name,
        'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
        'total' => $invoice->grand_total,
        'paid' => $invoice->paid_amount ?? 0,
    ])->values()->all()
) !!};
</script>

<script src="{{ asset('js/payment-form.js') }}?v=1" defer></script>
@endpush
