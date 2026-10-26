{{-- Sales report filters, summaries, and results. --}}
@extends('layouts.app')

@section('title', 'Sales Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=30">
@endpush

@section('content')
<div class="sr-page">



    {{-- FILTER BAR --}}
    <form action="{{ route('reports.sales') }}"
          method="GET"
          class="sr-filters">

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
            <label>Customer</label>

            <select name="customer_id">
                <option value="">All Customers</option>

                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        @selected((string)$customerId === (string)$customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sr-field">
            <label>Product</label>

            <select name="product_id">
                <option value="">All Products</option>

                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                        @selected((string)$productId === (string)$product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sr-field">
            <label>Payment Status</label>

            <select name="payment_status">
                <option value="">All Statuses</option>
                <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
                <option value="partial" @selected($paymentStatus === 'partial')>Partial</option>
                <option value="unpaid" @selected($paymentStatus === 'unpaid')>Unpaid</option>
            </select>
        </div>

        <div class="sr-filter-buttons">
            <button class="sr-button sr-primary" type="submit">
                <svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                Generate Report
            </button>

            <a href="{{ route('reports.sales') }}"
               class="sr-button sr-reset">
                <svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 1 1 2 6M3 4v7h7"/></svg>
                Reset
            </a>
        </div>
    </form>

    {{-- SUMMARY CARDS --}}
    <div class="sr-summary">

        <div class="sr-summary-card sr-blue">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M2 3h3l3 13h12l2-9H6"/>
                    <circle cx="9" cy="20" r="1"/>
                    <circle cx="19" cy="20" r="1"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Total Invoices</div>
                <div class="sr-card-value">{{ number_format($totalInvoices) }}</div>
                <div class="sr-card-foot">{{ number_format($totalItemsSold) }} items ({{ number_format($totalCustomers) }} customers)</div>
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
                <div class="sr-card-label">Total Sales (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalSales, 2) }}</div>
                <div class="sr-card-foot">Active invoice grand totals</div>
            </div>
        </div>

        <div class="sr-summary-card sr-purple">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Completed Payments (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalPaid ?? 0, 2) }}</div>
                <div class="sr-card-foot">Collected on filtered invoices</div>
            </div>
        </div>

        <div class="sr-summary-card sr-yellow">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Outstanding Balance (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalOutstanding ?? 0, 2) }}</div>
                <div class="sr-card-foot">Unpaid &amp; partial receivables</div>
            </div>
        </div>

    </div>

    {{-- CHARTS --}}
    <div class="sr-chart-grid">

        <section class="sr-panel">
            <div class="sr-chart-heading">
                <h2>Monthly Sales (Rs.)</h2>

                <div class="sr-chart-legend">
                    <span><i class="sr-legend-current"></i>{{ $year }}</span>
                    <span><i class="sr-legend-previous"></i>{{ $year - 1 }}</span>
                </div>
            </div>

            <div class="sr-canvas-wrap">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </section>

        <section class="sr-panel">
            <div class="sr-chart-heading">
                <h2>Top 5 Products by Sales (Rs.)</h2>
            </div>

            <div class="sr-canvas-wrap">
                <canvas id="topProductsChart"></canvas>
            </div>
        </section>

    </div>

    {{-- INVOICE DETAILS --}}
    <section class="sr-panel sr-table-panel">

        <div class="sr-table-heading">
            <h2>Sales Invoice Details</h2>

            <div class="sr-export-buttons">
                <a href="{{ route('reports.sales', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="sr-button sr-pdf"
                   title="Open full filtered report for Save as PDF">
                    <svg viewBox="0 0 24 24"><path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/></svg>
                    Export PDF
                </a>

                <a class="sr-button sr-excel"
                   href="{{ route('reports.sales.export', request()->query()) }}">
                    <svg viewBox="0 0 24 24"><path d="M6 2h12v20H6zM9 9l6 6M15 9l-6 6"/></svg>
                    Export Excel
                </a>

                <a href="{{ route('reports.sales', array_merge(request()->query(), ['print' => 1])) }}"
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
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Items</th>
                        <th>Invoice Total (Rs.)</th>
                        <th>Completed Paid (Rs.)</th>
                        <th>Outstanding (Rs.)</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoices->firstItem() + $loop->index }}</td>

                            <td class="sr-link-text">
                                {{ $invoice->invoice_number }}
                            </td>

                            <td>{{ $invoice->customer?->name ?? '—' }}</td>

                            <td>
                                {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}
                            </td>

                            <td>{{ $invoice->items->sum('quantity') }}</td>

                            <td>{{ number_format($invoice->grand_total, 2) }}</td>

                            <td>{{ number_format($invoice->completed_paid ?? 0, 2) }}</td>

                            <td>{{ number_format($invoice->outstanding_balance ?? 0, 2) }}</td>

                            <td>
                                <span class="sr-status sr-status-{{ $invoice->payment_status }}">
                                    {{ ucfirst($invoice->payment_status) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="sr-eye"
                                   aria-label="View invoice {{ $invoice->invoice_number }}">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="sr-no-data">
                                No sales invoices found for this report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sr-table-footer">
            <span>
                Showing {{ $invoices->firstItem() ?? 0 }}
                to {{ $invoices->lastItem() ?? 0 }}
                of {{ $invoices->total() }} entries
            </span>

            @if(empty($isPrintMode))
                <div class="sr-pagination">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>

    </section>
</div>
@endsection

@push('scripts')
<script>
    window.salesReportData = {
        year: @json($year),
        monthly: @json($monthlySales),
        products: @json($topProducts)
    };
    @if(!empty($isPrintMode))
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 350);
    });
    @endif
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/reports.js') }}?v=30" defer></script>
@endpush
