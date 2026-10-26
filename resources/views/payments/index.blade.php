{{-- Payment list and search filters. --}}
@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<div class="pay-page">

    <section class="pay-card pay-index-card">

        @if(session('success'))
            <div class="pay-alert pay-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="pay-alert pay-alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET"
              action="{{ route('payments.index') }}"
              class="pay-toolbar">

            <div class="pay-search">
                <svg viewBox="0 0 24 24">
                    <circle cx="10.5" cy="10.5" r="6.5"/>
                    <path d="m16 16 5 5"/>
                </svg>
                <input
                    type="search"
                    id="search"
                    name="search"
                    placeholder="Search payments..."
                    value="{{ request('search') }}"
                    aria-label="Search payments"
                >
            </div>

            <div class="pay-filter">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method">
                    <option value="">All Methods</option>

                    @foreach([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'online_payment' => 'Online Payment'
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('payment_method') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pay-filter">
                <label for="from_date">From Date</label>
                <input
                    type="date"
                    id="from_date"
                    name="from_date"
                    value="{{ request('from_date') }}"
                >
            </div>

            <div class="pay-filter">
                <label for="to_date">To Date</label>
                <input
                    type="date"
                    id="to_date"
                    name="to_date"
                    value="{{ request('to_date') }}"
                >
            </div>

            <div class="pay-filter">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>

                    @foreach([
                        'completed' => 'Completed',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'reversed' => 'Reversed'
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('status') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="pay-filter-submit">
                Filter
            </button>

            <a href="{{ route('payments.index') }}"
               class="pay-filter-reset">
                Reset
            </a>

            <a href="{{ route('payments.create') }}"
               class="pay-add">
                <span>＋</span> Add Payment
            </a>

        </form>

        <div class="pay-table-wrap">
            <table class="pay-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment No</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Amount (Rs.)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                {{ $payment->list_serial ?? ($payments->firstItem() + $loop->index) }}
                            </td>

                            <td>
                                <a class="pay-link"
                                   href="{{ route('payments.show', $payment) }}">
                                    {{ $payment->payment_number ?? 'PAY-'.$payment->id }}
                                </a>
                            </td>

                            <td>
                                {{ $payment->invoice?->invoice_number ?? '—' }}
                            </td>

                            <td>
                                {{ $payment->invoice?->customer?->name ?? '—' }}
                            </td>

                            <td>
                                {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                            </td>

                            <td>
                                {{ number_format($payment->amount, 2) }}
                            </td>

                            <td>
                                @php
                                    $statusClass = match($payment->status) {
                                        'completed' => 'pay-status-completed',
                                        'pending' => 'pay-status-pending',
                                        'failed' => 'pay-status-failed',
                                        'reversed' => 'pay-status-reversed',
                                        default => 'pay-status-default'
                                    };
                                @endphp

                                <span class="pay-status-badge {{ $statusClass }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('payments.show', $payment) }}"
                                   class="pay-link"
                                   title="View payment"
                                   aria-label="View payment {{ $payment->payment_number ?? 'PAY-'.$payment->id }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="9" class="pay-empty">
                                No payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="pay-pagination">
            <span>
                @if($payments->total())
                    Showing
                    {{ $payments->firstItem() }}
                    to
                    {{ $payments->lastItem() }}
                    of
                    {{ $payments->total() }}
                    entries
                @else
                    Showing 0 entries
                @endif
            </span>

            <div class="pay-pagination-links">
                @if($payments->onFirstPage())
                    <span class="pay-disabled">‹</span>
                @else
                    <a href="{{ $payments->previousPageUrl() }}">‹</a>
                @endif

                @foreach($payments->getUrlRange(
                    max(1, $payments->currentPage() - 2),
                    min($payments->lastPage(), $payments->currentPage() + 2)
                ) as $page => $url)
                    <a href="{{ $url }}"
                       class="{{ $page === $payments->currentPage() ? 'active' : '' }}"
                       @if($page === $payments->currentPage()) aria-current="page" @endif>
                        {{ $page }}
                    </a>
                @endforeach

                @if($payments->hasMorePages())
                    <a href="{{ $payments->nextPageUrl() }}">›</a>
                @else
                    <span class="pay-disabled">›</span>
                @endif
            </div>
        </div>

    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payments.css') }}?v=12">
@endpush
