{{-- Customer balances and outstanding invoice report. --}}
@extends('layouts.app')

@section('title', 'Customer Outstanding Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=30">
@endpush

@section('content')
<div class="sr-page">



    @if(($anomalyCount ?? 0) > 0)
        <div style="background: #fff4e5; border: 1px solid #ffd599; color: #8a5300; padding: 10px 13px; border-radius: 7px; margin-bottom: 12px; font-size: 11.5px;">
            <strong>Audit Notice:</strong> {{ $anomalyCount }} customer account(s) have completed payments exceeding active invoice totals. These are flagged below for review.
        </div>
    @endif

    {{-- FILTER BAR --}}
    <form action="{{ route('reports.customer-outstanding') }}"
          method="GET"
          class="sr-filters"
          style="grid-template-columns: minmax(200px, 1.4fr) minmax(180px, 1.2fr) minmax(150px, 1fr) auto;">

        <div class="sr-field">
            <label>Search Customer</label>
            <input type="search"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Name, contact, phone, email..."
                   style="width: 100%; height: 35px; padding: 0 10px; border: 1px solid #d9e5f5; border-radius: 5px; font-size: 11px; color: #254579;">
        </div>

        <div class="sr-field">
            <label>Customer</label>

            <select name="customer_id">
                <option value="">All Customers</option>

                @foreach($customerOptions as $option)
                    <option value="{{ $option->id }}"
                        @selected((string)$customerId === (string)$option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sr-field">
            <label>Balance Status</label>

            <select name="balance_status">
                <option value="">All Customers</option>
                <option value="outstanding" @selected($balanceStatus === 'outstanding')>With Outstanding Balance</option>
                <option value="settled" @selected($balanceStatus === 'settled')>Settled (Rs. 0.00 Due)</option>
            </select>
        </div>

        <div class="sr-filter-buttons">
            <button class="sr-button sr-primary" type="submit">
                <svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                Generate Report
            </button>

            <a href="{{ route('reports.customer-outstanding') }}"
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
                    <circle cx="9" cy="8" r="3"/>
                    <path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 4 5"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Customers with Balance</div>
                <div class="sr-card-value">{{ number_format($customersWithBalanceCount) }}</div>
                <div class="sr-card-foot">Out of {{ number_format($totalCustomersCount) }} filtered customers</div>
            </div>
        </div>

        <div class="sr-summary-card sr-purple">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M5 2h10l4 4v16H5zM15 2v5h4M9 12h6M9 16h6"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Total Invoiced (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalInvoiced, 2) }}</div>
                <div class="sr-card-foot">Active invoices (grand_total)</div>
            </div>
        </div>

        <div class="sr-summary-card sr-green">
            <div class="sr-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <div>
                <div class="sr-card-label">Completed Payments (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalPaid, 2) }}</div>
                <div class="sr-card-foot">Excludes reversed payments</div>
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
                <div class="sr-card-label">Total Outstanding (Rs.)</div>
                <div class="sr-card-value">{{ number_format($totalOutstanding, 2) }}</div>
                <div class="sr-card-foot">Total receivable balance due</div>
            </div>
        </div>

    </div>

    {{-- CUSTOMER OUTSTANDING TABLE --}}
    <section class="sr-panel sr-table-panel">

        <div class="sr-table-heading">
            <h2>Customer Receivable Balances</h2>

            <div class="sr-export-buttons">
                <a href="{{ route('reports.customer-outstanding', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="sr-button sr-pdf"
                   title="Open full filtered report for Save as PDF">
                    <svg viewBox="0 0 24 24"><path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/></svg>
                    Export PDF
                </a>

                <a class="sr-button sr-excel"
                   href="{{ route('reports.customer-outstanding.export', request()->query()) }}">
                    <svg viewBox="0 0 24 24"><path d="M6 2h12v20H6zM9 9l6 6M15 9l-6 6"/></svg>
                    Export Excel
                </a>

                <a href="{{ route('reports.customer-outstanding', array_merge(request()->query(), ['print' => 1])) }}"
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
                        <th>Customer</th>
                        <th>Contact / Phone</th>
                        <th>Active Invoices</th>
                        <th>Total Invoiced (Rs.)</th>
                        <th>Completed Paid (Rs.)</th>
                        <th>Outstanding Balance (Rs.)</th>
                        <th>Payment Status Summary</th>
                        <th>Statement</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($customersReport as $customer)
                        <tr>
                            <td>{{ $customersReport->firstItem() + $loop->index }}</td>

                            <td class="sr-link-text">
                                {{ $customer->name }}
                                @if(!$customer->is_active)
                                    <span style="font-size: 9px; color: #c84a53; font-weight: 700;">(Inactive)</span>
                                @endif
                            </td>

                            <td>
                                {{ $customer->contact_person ?: '—' }}
                                @if($customer->phone)
                                    <span style="color: #6b84a6; font-size: 10px;">
                                        ({{ $customer->phone }})
                                    </span>
                                @endif
                            </td>

                            <td>{{ number_format((int) $customer->active_invoices_count) }}</td>

                            <td>{{ number_format($customer->total_invoiced, 2) }}</td>

                            <td>{{ number_format($customer->completed_paid, 2) }}</td>

                            <td>
                                <strong>{{ number_format($customer->outstanding_balance, 2) }}</strong>
                                @if($customer->overpayment_cents > 0)
                                    <div style="font-size: 9.5px; color: #b91c1c; font-weight: 700;">
                                        Overpaid by Rs. {{ number_format($customer->overpayment_amount, 2) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @php
                                    $statusClass = match($customer->overall_status) {
                                        'paid', 'no_invoices' => 'sr-status-paid',
                                        'partial' => 'sr-status-partial',
                                        default => 'sr-status-unpaid',
                                    };
                                @endphp
                                <span class="sr-status {{ $statusClass }}" style="margin-right: 6px;">
                                    {{ $customer->overall_label }}
                                </span>
                                <span style="font-size: 10px; color: #5b76a3;">
                                    {{ $customer->status_breakdown }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('customers.statement', $customer->id) }}"
                                   class="sr-eye"
                                   title="View Customer Statement"
                                   aria-label="View statement for {{ $customer->name }}"
                                   style="gap: 4px; text-decoration: none; font-weight: 700; font-size: 11px;">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M6 2h9l4 4v16H6z"/>
                                        <path d="M15 2v5h4M9 13h6M9 17h6"/>
                                    </svg>
                                    Statement
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="sr-no-data">
                                No customer accounts found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sr-table-footer">
            <span>
                Showing {{ $customersReport->firstItem() ?? 0 }}
                to {{ $customersReport->lastItem() ?? 0 }}
                of {{ $customersReport->total() }} entries
            </span>

            @if(empty($isPrintMode))
                <div class="sr-pagination">
                    {{ $customersReport->links() }}
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
