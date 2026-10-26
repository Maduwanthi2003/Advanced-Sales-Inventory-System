{{-- Customer statement, balances, and transaction history. --}}
@extends('layouts.app')

@section('title', 'Customer Statement — ' . $customer->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customer-statement.css') }}?v=1">
@endpush

@section('content')
<div class="cs-page">

    {{-- Top Actions --}}
    <div class="cs-heading" style="justify-content: flex-end;">
        <div class="cs-top-actions">
            <a href="{{ route('customers.index') }}" class="cs-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Customers
            </a>

            <a href="{{ route('customers.statement.print', array_filter(['customer' => $customer->id, 'from_date' => $fromDate, 'to_date' => $toDate])) }}"
               target="_blank"
               rel="noopener"
               class="cs-btn cs-btn-pdf"
               title="Open printable statement and choose Save as PDF">
                <svg viewBox="0 0 24 24">
                    <path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/>
                </svg>
                Save as PDF
            </a>

            <a href="{{ route('customers.statement.print', array_filter(['customer' => $customer->id, 'from_date' => $fromDate, 'to_date' => $toDate])) }}"
               target="_blank"
               rel="noopener"
               class="cs-btn cs-btn-primary">
                <svg viewBox="0 0 24 24">
                    <path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5h-2M6 14h12v7H6z"/>
                </svg>
                Print Statement
            </a>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="cs-alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Customer Information & Statement Period --}}
    <section class="cs-panel">
        <div class="cs-info-grid">
            <div class="cs-info-block">
                <h2>
                    {{ $customer->name }}
                    <span class="cs-status-badge {{ $customer->is_active ? 'cs-status-active' : 'cs-status-inactive' }}">
                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </h2>

                <div class="cs-meta-list">
                    <div class="cs-meta-item">
                        <span>Contact Person</span>
                        <strong>{{ $customer->contact_person ?: '—' }}</strong>
                    </div>

                    <div class="cs-meta-item">
                        <span>Phone</span>
                        <strong>{{ $customer->phone ?: '—' }}</strong>
                    </div>

                    <div class="cs-meta-item">
                        <span>Email</span>
                        <strong>{{ $customer->email ?: '—' }}</strong>
                    </div>

                    <div class="cs-meta-item">
                        <span>Address</span>
                        <strong>
                            @php
                                $addressParts = array_filter([
                                    $customer->address,
                                    $customer->city,
                                    $customer->postal_code,
                                    $customer->country,
                                ]);
                            @endphp
                            {{ !empty($addressParts) ? implode(', ', $addressParts) : '—' }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="cs-period-box">
                <div class="cs-period-row">
                    <span>Statement Period</span>
                    <strong>{{ $periodLabel }}</strong>
                </div>
                <div class="cs-period-row">
                    <span>Generated On</span>
                    <strong>{{ $generatedAt->format('d M Y, H:i') }}</strong>
                </div>
                <div class="cs-period-row">
                    <span>Period Transactions</span>
                    <strong>{{ count($entries) }}</strong>
                </div>
                <div class="cs-period-row">
                    <span>Closing Receivable Balance</span>
                    <strong>Rs. {{ number_format($closingBalance, 2) }}</strong>
                </div>
            </div>
        </div>
    </section>

    {{-- Date Range Filter Bar --}}
    <section class="cs-panel cs-filter-panel">
        <form method="GET"
              action="{{ route('customers.statement', $customer) }}"
              class="cs-filter-form">

            <div class="cs-filter-fields">
                <div class="cs-field">
                    <label for="from_date">From Date</label>
                    <input type="date"
                           id="from_date"
                           name="from_date"
                           value="{{ old('from_date', $fromDate) }}">
                </div>

                <div class="cs-field">
                    <label for="to_date">To Date</label>
                    <input type="date"
                           id="to_date"
                           name="to_date"
                           value="{{ old('to_date', $toDate) }}">
                </div>
            </div>

            <div class="cs-filter-actions">
                <button type="submit" class="cs-btn cs-btn-primary">
                    <svg viewBox="0 0 24 24">
                        <circle cx="10.5" cy="10.5" r="6.5"/>
                        <path d="m16 16 5 5"/>
                    </svg>
                    Apply Filter
                </button>

                <a href="{{ route('customers.statement', $customer) }}"
                   class="cs-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 11a9 9 0 1 1 2 6M3 4v7h7"/>
                    </svg>
                    Reset Filter
                </a>
            </div>
        </form>
    </section>

    {{-- Financial Summary Cards --}}
    <section class="cs-summary-grid">
        <article class="cs-summary-card cs-card-blue">
            <div class="cs-card-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
            </div>
            <div class="cs-card-body">
                <div class="cs-card-label">Opening Balance</div>
                <div class="cs-card-value">Rs. {{ number_format($openingBalance, 2) }}</div>
                <div class="cs-card-foot">
                    {{ $fromDate ? 'Prior to ' . $fromDate : 'Start of account' }}
                </div>
            </div>
        </article>

        <article class="cs-summary-card cs-card-purple">
            <div class="cs-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/>
                </svg>
            </div>
            <div class="cs-card-body">
                <div class="cs-card-label">Total Invoiced (Debit)</div>
                <div class="cs-card-value">Rs. {{ number_format($periodInvoiced, 2) }}</div>
                <div class="cs-card-foot">Active invoices in period</div>
            </div>
        </article>

        <article class="cs-summary-card cs-card-green">
            <div class="cs-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <div class="cs-card-body">
                <div class="cs-card-label">Total Payments (Credit)</div>
                <div class="cs-card-value">Rs. {{ number_format($periodPaid, 2) }}</div>
                <div class="cs-card-foot">Completed payments in period</div>
            </div>
        </article>

        <article class="cs-summary-card cs-card-amber">
            <div class="cs-card-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
            </div>
            <div class="cs-card-body">
                <div class="cs-card-label">Closing Balance</div>
                <div class="cs-card-value">Rs. {{ number_format($closingBalance, 2) }}</div>
                <div class="cs-card-foot">Opening + Invoiced − Payments</div>
            </div>
        </article>
    </section>

    {{-- Chronological Transaction Ledger --}}
    <section class="cs-panel cs-table-panel">
        <div class="cs-table-header">
            <h2>Receivables Transaction Ledger</h2>
            <span class="cs-table-subtitle">
                Chronological active invoices (Debit) and completed payments (Credit)
            </span>
        </div>

        <div class="cs-table-wrap">
            <table class="cs-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="cs-num">Invoice Debit (Rs.)</th>
                        <th class="cs-num">Payment Credit (Rs.)</th>
                        <th class="cs-num">Running Balance (Rs.)</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- Opening Balance Row --}}
                    <tr class="cs-opening-row">
                        <td>{{ $fromDate ?? '—' }}</td>
                        <td>—</td>
                        <td>—</td>
                        <td>Opening Balance Brought Forward</td>
                        <td class="cs-num">—</td>
                        <td class="cs-num">—</td>
                        <td class="cs-num cs-balance">
                            {{ number_format($openingBalance, 2) }}
                        </td>
                    </tr>

                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry['date'] }}</td>
                            <td>
                                @if(Route::has($entry['route_name']))
                                    <a href="{{ route($entry['route_name'], $entry['route_param']) }}"
                                       class="cs-ref-link">
                                        {{ $entry['reference'] }}
                                    </a>
                                @else
                                    {{ $entry['reference'] }}
                                @endif
                            </td>
                            <td>
                                <span class="cs-type-badge {{ $entry['type'] === 'invoice' ? 'cs-type-invoice' : 'cs-type-payment' }}">
                                    {{ $entry['type_label'] }}
                                </span>
                            </td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="cs-num cs-debit">
                                {{ $entry['debit_cents'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                            </td>
                            <td class="cs-num cs-credit">
                                {{ $entry['credit_cents'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                            </td>
                            <td class="cs-num cs-balance">
                                {{ number_format($entry['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="cs-empty">
                                No active invoices or completed payments recorded during the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="cs-closing-row">
                        <td colspan="4">Period Totals &amp; Closing Balance</td>
                        <td class="cs-num">
                            {{ number_format($periodInvoiced, 2) }}
                        </td>
                        <td class="cs-num">
                            {{ number_format($periodPaid, 2) }}
                        </td>
                        <td class="cs-num">
                            {{ number_format($closingBalance, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="cs-accounting-note">
            <strong>Accounting Policy Note:</strong>
            Statement balances are calculated from current active receivables
            (<code>invoices.status = 'active'</code>) and completed payments
            (<code>payments.status = 'completed'</code>). Cancelled invoices and reversed
            payments are excluded from financial totals and listed separately below for audit verification.
        </div>
    </section>

    {{-- Separated Audit Section for Cancelled Invoices & Reversed Payments --}}
    @if(!empty($auditEntries))
        <section class="cs-panel cs-table-panel">
            <div class="cs-table-header">
                <h2>Voided &amp; Reversed Transactions (Audit Reference Only — Excluded from Balances)</h2>
                <span class="cs-table-subtitle">
                    Historical cancelled invoices and reversed payments within the selected period
                </span>
            </div>

            <div class="cs-table-wrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Audit Details</th>
                            <th class="cs-num">Original Amount (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditEntries as $audit)
                            <tr>
                                <td>{{ $audit['date'] }}</td>
                                <td>
                                    @if(Route::has($audit['route_name']))
                                        <a href="{{ route($audit['route_name'], $audit['route_param']) }}"
                                           class="cs-ref-link">
                                            {{ $audit['reference'] }}
                                        </a>
                                    @else
                                        {{ $audit['reference'] }}
                                    @endif
                                </td>
                                <td>
                                    <span class="cs-type-badge cs-type-void">
                                        {{ $audit['status_label'] }}
                                    </span>
                                </td>
                                <td>{{ $audit['details'] }}</td>
                                <td class="cs-num">
                                    {{ number_format($audit['amount'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

</div>
@endsection
