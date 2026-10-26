{{-- Invoice list and management actions. --}}
@extends('layouts.app')

@section('title', 'Sales Invoices')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/invoices.css') }}?v=1">
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $canCreateInvoice = $authUser?->hasPermission('invoices.create') ?? false;
    $canEditInvoice = $authUser?->hasPermission('invoices.edit') ?? false;
@endphp
<div class="iv-page">


    @if(session('success'))
        <div class="iv-alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="iv-alert iv-alert-error">{{ session('error') }}</div>
    @endif

    <section class="iv-card">
        <form action="{{ route('invoices.index') }}"
              method="GET" class="iv-filters">

            <input type="search" name="search"
                   value="{{ request('search') }}"
                   placeholder="⌕  Search invoices...">

            <div class="iv-filter-field">
                <label>Customer</label>
                <select name="customer_id">
                    <option value="">All Customers</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                            @selected(request('customer_id') == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="iv-filter-field">
                <label>From Date</label>
                <input type="date" name="from_date"
                       value="{{ request('from_date') }}">
            </div>

            <div class="iv-filter-field">
                <label>To Date</label>
                <input type="date" name="to_date"
                       value="{{ request('to_date') }}">
            </div>

            <div class="iv-filter-field">
                <label>Status</label>
                <select name="payment_status">
                    <option value="">All Status</option>
                    @foreach(['unpaid', 'partial', 'paid', 'cancelled'] as $status)
                        <option value="{{ $status }}"
                            @selected(request('payment_status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="iv-filter-btn">Filter</button>

            @if($canCreateInvoice)
                <a href="{{ route('invoices.create') }}"
                   class="iv-primary-btn">
                    ＋ Add Invoice
                </a>
            @endif
        </form>

        <div class="iv-table-scroll">
            <table class="iv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Items</th>
                        <th>Total Amount (Rs.)</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $isEditable = $invoice->status !== 'cancelled'
                                && $invoice->payment_status === 'unpaid'
                                && (int) ($invoice->payments_count ?? 0) === 0;
                        @endphp
                        <tr>
                            <td>{{ $invoice->list_serial ?? ($invoices->firstItem() + $loop->index) }}</td>

                            <td>
                                <a class="iv-invoice-link"
                                   href="{{ route('invoices.show', $invoice) }}">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>

                            <td>{{ $invoice->customer?->name ?? '—' }}</td>

                            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>

                            <td>{{ $invoice->items_count }}</td>

                            <td>{{ number_format($invoice->grand_total, 2) }}</td>

                            <td>
                                @if($invoice->status === 'cancelled')
                                    <span class="iv-status iv-unpaid">
                                        Cancelled
                                    </span>
                                @else
                                    <span class="iv-status iv-{{ $invoice->payment_status }}">
                                        {{ ucfirst($invoice->payment_status) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="iv-actions">
                                    <a href="{{ route('invoices.show', $invoice) }}"
                                       title="View Invoice"
                                       aria-label="View Invoice">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    @if($canEditInvoice && $isEditable)
                                        <a href="{{ route('invoices.edit', $invoice) }}"
                                           title="Edit Invoice"
                                           aria-label="Edit Invoice">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                            </svg>
                                        </a>

                                        <form method="POST"
                                              action="{{ route('invoices.cancel', $invoice) }}"
                                              onsubmit="return confirm('Cancel this invoice and restore stock?');"
                                              style="display: inline-flex; margin: 0;">
                                            @csrf
                                            <button type="submit"
                                                    title="Cancel Invoice"
                                                    aria-label="Cancel Invoice"
                                                    style="background: none; border: none; padding: 0; color: #d02b43; cursor: pointer; display: inline-flex;">
                                                <svg viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <path d="m15 9-6 6M9 9l6 6"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="iv-empty">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="iv-pagination">
            <span>
                Showing {{ $invoices->firstItem() ?? 0 }}
                to {{ $invoices->lastItem() ?? 0 }}
                of {{ $invoices->total() }} entries
            </span>

            <div class="iv-page-links">
                @if($invoices->onFirstPage())
                    <span class="iv-page-disabled">‹</span>
                @else
                    <a href="{{ $invoices->previousPageUrl() }}">‹</a>
                @endif

                @foreach($invoices->getUrlRange(
                    max(1, $invoices->currentPage() - 2),
                    min($invoices->lastPage(), $invoices->currentPage() + 2)
                ) as $page => $url)
                    <a href="{{ $url }}"
                       class="{{ $page == $invoices->currentPage() ? 'active' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach

                @if($invoices->hasMorePages())
                    <a href="{{ $invoices->nextPageUrl() }}">›</a>
                @else
                    <span class="iv-page-disabled">›</span>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
