{{-- Purchase report filters, summaries, and results. --}}
@extends('layouts.app')

@section('title', 'Purchase Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=30">
@endpush

@section('content')
<div class="sr-page">



    {{-- FILTER BAR --}}
    <form action="{{ route('reports.purchases') }}"
          method="GET"
          class="sr-filters"
          style="grid-template-columns: minmax(240px, 1.5fr) minmax(160px, 1.2fr) minmax(130px, 1fr) auto;">

        <div class="sr-field sr-date-field">
            <label>Date Range</label>

            <div class="sr-date-inputs">
                <input type="date"
                       name="from_date"
                       aria-label="From date"
                       value="{{ $fromDate }}"
                       required>

                <span>to</span>

                <input type="date"
                       name="to_date"
                       aria-label="To date"
                       value="{{ $toDate }}"
                       required>
            </div>
        </div>

        <div class="sr-field">
            <label>Supplier</label>

            <select name="supplier_id">
                <option value="">All Suppliers</option>

                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"
                        @selected((string)$supplierId === (string)$supplier->id)>
                        {{ $supplier->name }}{{ $supplier->company_name ? ' (' . $supplier->company_name . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sr-field">
            <label>Purchase Status</label>

            <select name="status">
                <option value="">Active (Non-Cancelled)</option>
                <option value="completed" @selected($status === 'completed')>Completed</option>
                <option value="received" @selected($status === 'received')>Received</option>
                <option value="partial" @selected($status === 'partial')>Partial</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
        </div>

        <div class="sr-filter-buttons">
            <button class="sr-button sr-primary" type="submit">
                <svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                Generate Report
            </button>

            <a href="{{ route('reports.purchases') }}"
               class="sr-button sr-reset">
                <svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 2 6M3 4v7h7"/></svg>
                Reset
            </a>
        </div>
    </form>

    {{-- SUMMARY CARDS (Active / Non-Cancelled Purchases) --}}
    <div class="sr-summary">

        <div class="sr-summary-card sr-blue">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="7" width="18" height="14" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Total Purchases</div>
                <div class="sr-card-value">{{ number_format($totalPurchases) }}</div>
                <div class="sr-card-foot">Active purchases in period</div>
            </div>
        </div>

        <div class="sr-summary-card sr-green">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M15 8c-2-2-6-1-6 1 0 3 6 1 6 5 0 3-5 4-7 1M12 5v14"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Total Purchases (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalPurchaseAmount, 2) }}</div>
                <div class="sr-card-foot">Excludes cancelled purchases</div>
            </div>
        </div>

        <div class="sr-summary-card sr-purple">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m12 2 9 5v10l-9 5-9-5V7zM3 7l9 5 9-5M12 12v10"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Total Items Received</div>
                <div class="sr-card-value">{{ number_format($totalItemsPurchased) }}</div>
                <div class="sr-card-foot">Purchased stock quantity</div>
            </div>
        </div>

        <div class="sr-summary-card sr-yellow">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="1" y="3" width="15" height="13"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Active Suppliers</div>
                <div class="sr-card-value">{{ number_format($totalSuppliers) }}</div>
                <div class="sr-card-foot">Unique suppliers in period</div>
            </div>
        </div>

    </div>

    {{-- PURCHASE DETAILS TABLE --}}
    <section class="sr-panel sr-table-panel">

        <div class="sr-table-heading">
            <h2>Purchase Order Details</h2>

            <div class="sr-export-buttons">
                <a href="{{ route('reports.purchases', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="sr-button sr-pdf"
                   title="Open full filtered report for Save as PDF">
                    <svg viewBox="0 0 24 24"><path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/></svg>
                    Export PDF
                </a>

                <a class="sr-button sr-excel"
                   href="{{ route('reports.purchases.export', request()->query()) }}">
                    <svg viewBox="0 0 24 24"><path d="M6 2h12v20H6zM9 9l6 6M15 9l-6 6"/></svg>
                    Export Excel
                </a>

                <a href="{{ route('reports.purchases', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="sr-button sr-print">
                    <svg viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5h-2M6 14h12v7H6z"/></svg>
                    Print
                </a>
            </div>
        </div>

        <div class="sr-table-scroll">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purchase No</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                        <th>Total Items</th>
                        <th>Purchase Total (Rs.)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchases->firstItem() + $loop->index }}</td>

                            <td class="sr-link-text">
                                {{ $purchase->purchase_number }}
                            </td>

                            <td>
                                {{ $purchase->supplier?->name ?? '—' }}
                                @if($purchase->supplier?->company_name)
                                    <span style="color: #6b84a6; font-size: 10px;">
                                        ({{ $purchase->supplier->company_name }})
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}
                            </td>

                            <td>{{ number_format((int) ($purchase->total_items_qty ?? 0)) }}</td>

                            <td>{{ number_format($purchase->total_amount, 2) }}</td>

                            <td>
                                @php
                                    $badgeClass = match($purchase->status) {
                                        'completed', 'received' => 'sr-status-paid',
                                        'partial' => 'sr-status-partial',
                                        default => 'sr-status-unpaid',
                                    };
                                @endphp
                                <span class="sr-status {{ $badgeClass }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="sr-eye"
                                   aria-label="View purchase {{ $purchase->purchase_number }}">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="sr-no-data">
                                No purchases found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sr-table-footer">
            <span>
                Showing {{ $purchases->firstItem() ?? 0 }}
                to {{ $purchases->lastItem() ?? 0 }}
                of {{ $purchases->total() }} entries
            </span>

            @if(empty($isPrintMode))
                <div class="sr-pagination">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>

    </section>
</div>
@endsection

@if(!empty($isPrintMode))
@push('scripts')
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 250);
    });
</script>
@endpush
@endif
