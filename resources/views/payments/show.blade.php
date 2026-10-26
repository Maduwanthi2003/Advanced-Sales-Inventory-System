
@extends('layouts.app')

@section('content')
<div class="pay-page">



    {{-- Success Message --}}
    @if(session('success'))
        <div class="pay-alert pay-alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div class="pay-alert pay-alert-error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="pay-alert pay-alert-error" role="alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Payment Details Card --}}
    <div class="pay-card pay-form-card">

        <div class="pay-section-title">
            <h2>
                {{ $payment->payment_number ?? 'Payment Details' }}
            </h2>

            <p>Recorded payment information.</p>
        </div>

        {{-- Payment Information --}}
        <div class="pay-form-grid">

            {{-- Left Column --}}
            <div class="pay-form-column">

                <div class="pay-field">
                    <label>Invoice Number</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ $payment->invoice?->invoice_number ?? '—' }}"
                        >
                    </div>
                </div>

                <div class="pay-field">
                    <label>Customer</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ $payment->invoice?->customer?->name ?? '—' }}"
                        >
                    </div>
                </div>

                <div class="pay-field">
                    <label>Payment Date</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}"
                        >
                    </div>
                </div>

                <div class="pay-field">
                    <label>Payment Method</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}"
                        >
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="pay-form-column">

                <div class="pay-field">
                    <label>Amount (Rs.)</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ number_format($payment->amount, 2) }}"
                        >
                    </div>
                </div>

                <div class="pay-field">
                    <label>Payment Status</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ ucfirst($payment->status) }}"
                        >
                    </div>

                    <div class="pay-status-container">
                        @php
                            $statusClass = match($payment->status) {
                                'completed' => 'pay-status-completed',
                                'pending'   => 'pay-status-pending',
                                'failed'    => 'pay-status-failed',
                                'reversed'  => 'pay-status-reversed',
                                default     => 'pay-status-default',
                            };
                        @endphp

                        <span class="pay-status-badge {{ $statusClass }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>

                <div class="pay-field">
                    <label>Reference Number</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ $payment->reference_number ?? '—' }}"
                        >
                    </div>
                </div>

                <div class="pay-field">
                    <label>Recorded By</label>

                    <div class="pay-input-icon pay-readonly">
                        <input
                            type="text"
                            readonly
                            value="{{ $payment->user?->name ?? '—' }}"
                        >
                    </div>
                </div>

            </div>
        </div>

        {{-- Remarks --}}
        <div class="pay-field pay-remarks-field">
            <label>Remarks</label>

            <textarea
                readonly
                rows="3"
            >{{ $payment->notes ?? '—' }}</textarea>
        </div>

        {{-- Reversal Information --}}
        @if($payment->status === 'reversed')

            <div class="pay-reversal-details">

                <div class="pay-reversal-heading">
                    <h3>Payment Reversal Information</h3>

                    <span class="pay-status-badge pay-status-reversed">
                        Reversed
                    </span>
                </div>

                <p class="pay-reversal-description">
                    This payment has been reversed and is no longer
                    counted towards the invoice's paid amount.
                </p>

                <div class="pay-reversal-info-grid">

                    <div class="pay-field">
                        <label>Reversed Date</label>

                        <div class="pay-input-icon pay-readonly">
                            <input
                                type="text"
                                readonly
                                value="{{ $payment->reversed_at?->format('Y-m-d H:i') ?? '—' }}"
                            >
                        </div>
                    </div>

                    <div class="pay-field">
                        <label>Reversed By</label>

                        <div class="pay-input-icon pay-readonly">
                            <input
                                type="text"
                                readonly
                                value="{{ $payment->reversedBy?->name ?? '—' }}"
                            >
                        </div>
                    </div>

                </div>

                <div class="pay-field pay-reversal-reason">
                    <label>Reversal Reason</label>

                    <textarea readonly rows="3">{{ $payment->reversal_reason ?? '—' }}</textarea>
                </div>

            </div>

        @endif

        {{-- Action Buttons --}}
        <div class="pay-form-actions pay-details-actions">

            <a
                href="{{ route('payments.index') }}"
                class="pay-btn pay-btn-outline"
            >
                Back to Payments
            </a>

            <div class="pay-detail-actions">

                {{-- Print Receipt --}}
                <a
                    href="{{ route('payments.receipt', $payment) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pay-btn pay-btn-outline"
                >
                    Print Receipt
                </a>

                {{-- View Invoice --}}
                <a
                    href="{{ route('invoices.show', $payment->invoice_id) }}"
                    class="pay-btn pay-btn-primary"
                >
                    View Invoice
                </a>

            </div>

        </div>

    </div>

    {{-- Admin-only Payment Reversal --}}
    @can('reverse-payments')

        @if($payment->status === 'completed')

            <div class="pay-card pay-reversal-card">

                <div class="pay-section-title">
                    <h2>Reverse Payment</h2>

                    <p>
                        Reverse an incorrectly recorded payment.
                        The original payment record will be preserved
                        for audit purposes.
                    </p>
                </div>

                <div class="pay-reversal-warning">
                    <strong>Important:</strong>
                    Reversing this payment will update the invoice's
                    outstanding balance and payment status.
                    This action cannot be undone through this form.
                </div>

                <form
                    method="POST"
                    action="{{ route('payments.reverse', $payment) }}"
                    id="paymentReversalForm"
                >
                    @csrf

                    <div class="pay-field">
                        <label for="reversal_reason">
                            Reversal Reason
                            <span class="pay-required">*</span>
                        </label>

                        <textarea
                            id="reversal_reason"
                            name="reversal_reason"
                            rows="4"
                            minlength="10"
                            maxlength="2000"
                            required
                            placeholder="Enter the reason for reversing this payment (minimum 10 characters)"
                        >{{ old('reversal_reason') }}</textarea>

                        @error('reversal_reason')
                            <small class="pay-field-error">
                                {{ $message }}
                            </small>
                        @enderror

                        <small class="pay-field-help">
                            Please provide a clear explanation for audit records.
                        </small>
                    </div>

                    <div class="pay-reversal-actions">

                        <button
                            type="submit"
                            class="pay-btn pay-btn-danger"
                            id="reversePaymentButton"
                        >
                            Reverse Payment
                        </button>

                    </div>

                </form>

            </div>

        @endif

    @endcan

</div>
@endsection

@push('styles')
<link
    rel="stylesheet"
    href="{{ asset('css/payments.css') }}?v=2"
>
@endpush

@push('scripts')
<script src="{{ asset('js/payment-details.js') }}?v=1" defer></script>
@endpush
