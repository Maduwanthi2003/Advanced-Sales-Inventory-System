{{-- Printable customer account statement. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Statement — {{ $customer->name }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('laravel-icon.svg') }}">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #1c3552;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 12px;
        }

        .toolbar {
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border: 1px solid #cbd9ec;
            border-radius: 6px;
            background: #fff;
            color: #163b6d;
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #0878ff;
            border-color: #0878ff;
            color: #fff;
        }

        .sheet {
            max-width: 900px;
            margin: 0 auto 30px;
            padding: 38px 42px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 22px rgba(0, 0, 0, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 3px solid #0b2153;
            padding-bottom: 18px;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 26px;
            color: #0b2153;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 4px 0;
            color: #516b8f;
        }

        .right {
            text-align: right;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
            margin: 22px 0;
        }

        .details-box {
            padding: 14px 16px;
            border: 1px solid #dce8f6;
            border-radius: 6px;
            background: #f9fbff;
        }

        .details-box h3 {
            margin: 0 0 10px;
            font-size: 13px;
            color: #0b2153;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .details-box p {
            margin: 5px 0;
            line-height: 1.45;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 22px;
        }

        .summary-item {
            padding: 12px;
            border: 1px solid #d7e5f6;
            border-radius: 6px;
            background: #f4f8ff;
        }

        .summary-item span {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #4f6b95;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-item strong {
            font-size: 15px;
            color: #0b2153;
        }

        .section-title {
            margin: 22px 0 10px;
            font-size: 14px;
            font-weight: 750;
            color: #0b2153;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }

        thead th {
            background: #0b2153;
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e3ebf5;
            vertical-align: middle;
        }

        .opening-row td {
            background: #f4f8fe;
            font-weight: 700;
        }

        .closing-row td {
            background: #e9f2fc;
            font-weight: 800;
            border-top: 2px solid #0b2153;
        }

        .note {
            margin-top: 16px;
            padding: 10px 12px;
            background: #f8fafd;
            border: 1px solid #e1ebf7;
            border-radius: 5px;
            font-size: 10.5px;
            color: #546d8e;
            line-height: 1.45;
        }

        .footer {
            margin-top: 34px;
            padding-top: 14px;
            border-top: 1px solid #dce6f2;
            text-align: center;
            color: #6b809c;
            font-size: 11px;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        @media screen and (max-width: 720px) {
            .toolbar {
                padding: 0 12px;
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-actions {
                width: 100%;
            }

            .toolbar .btn {
                width: 100%;
            }

            .sheet {
                margin: 0 10px 20px;
                padding: 20px 14px;
            }

            .header {
                flex-direction: column;
                gap: 10px;
            }

            .header .right {
                text-align: left;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 430px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }

            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    </style>
    <link rel="stylesheet"
          href="{{ asset('css/button-system.css') }}?v=1">
</head>
<body>

<div class="toolbar">
    <a href="{{ route('customers.statement', array_filter(['customer' => $customer->id, 'from_date' => $fromDate, 'to_date' => $toDate])) }}"
       class="btn">
        ← Back to Statement
    </a>

    <div class="toolbar-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            Print / Save as PDF
        </button>
    </div>
</div>

<div class="sheet">
    <div class="header">
        <div>
            <h1>CUSTOMER STATEMENT</h1>
            <p><strong>Period:</strong> {{ $periodLabel }}</p>
        </div>

        <div class="right">
            <h2 style="margin: 0; color: #0b2153;">Sales &amp; Inventory System</h2>
            <p>Generated: {{ $generatedAt->format('d M Y, H:i') }}</p>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-box">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> {{ $customer->name }}</p>
            <p><strong>Contact Person:</strong> {{ $customer->contact_person ?: '—' }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone ?: '—' }}</p>
            <p><strong>Email:</strong> {{ $customer->email ?: '—' }}</p>
            @php
                $addressParts = array_filter([
                    $customer->address,
                    $customer->city,
                    $customer->postal_code,
                    $customer->country,
                ]);
            @endphp
            <p><strong>Address:</strong> {{ !empty($addressParts) ? implode(', ', $addressParts) : '—' }}</p>
        </div>

        <div class="details-box">
            <h3>Account Summary</h3>
            <p><strong>Opening Balance:</strong> Rs. {{ number_format($openingBalance, 2) }}</p>
            <p><strong>Period Invoiced:</strong> Rs. {{ number_format($periodInvoiced, 2) }}</p>
            <p><strong>Period Payments:</strong> Rs. {{ number_format($periodPaid, 2) }}</p>
            <p><strong>Closing Balance:</strong> Rs. {{ number_format($closingBalance, 2) }}</p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span>Opening Balance</span>
            <strong>Rs. {{ number_format($openingBalance, 2) }}</strong>
        </div>
        <div class="summary-item">
            <span>Total Invoiced</span>
            <strong>Rs. {{ number_format($periodInvoiced, 2) }}</strong>
        </div>
        <div class="summary-item">
            <span>Total Payments</span>
            <strong>Rs. {{ number_format($periodPaid, 2) }}</strong>
        </div>
        <div class="summary-item">
            <span>Closing Balance</span>
            <strong>Rs. {{ number_format($closingBalance, 2) }}</strong>
        </div>
    </div>

    <h3 class="section-title">Receivables Transaction Ledger</h3>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="right">Debit (Rs.)</th>
                    <th class="right">Credit (Rs.)</th>
                    <th class="right">Balance (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-row">
                    <td>{{ $fromDate ?? '—' }}</td>
                    <td>—</td>
                    <td>—</td>
                    <td>Opening Balance Brought Forward</td>
                    <td class="right">—</td>
                    <td class="right">—</td>
                    <td class="right">{{ number_format($openingBalance, 2) }}</td>
                </tr>

                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td><strong>{{ $entry['reference'] }}</strong></td>
                        <td>{{ $entry['type_label'] }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td class="right">
                            {{ $entry['debit_cents'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                        </td>
                        <td class="right">
                            {{ $entry['credit_cents'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                        </td>
                        <td class="right">
                            <strong>{{ number_format($entry['running_balance'], 2) }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 18px; color: #71829a;">
                            No active invoices or completed payments recorded during the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="closing-row">
                    <td colspan="4">Period Totals &amp; Closing Balance</td>
                    <td class="right">{{ number_format($periodInvoiced, 2) }}</td>
                    <td class="right">{{ number_format($periodPaid, 2) }}</td>
                    <td class="right">{{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(!empty($auditEntries))
        <h3 class="section-title">Voided &amp; Reversed Transactions (Audit Reference Only — Excluded from Balances)</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Audit Details</th>
                        <th class="right">Original Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditEntries as $audit)
                        <tr>
                            <td>{{ $audit['date'] }}</td>
                            <td>{{ $audit['reference'] }}</td>
                            <td>{{ $audit['status_label'] }}</td>
                            <td>{{ $audit['details'] }}</td>
                            <td class="right">{{ number_format($audit['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="note">
        <strong>Accounting Policy Note:</strong>
        Statement balances are calculated from current active receivables
        (<code>invoices.status = 'active'</code>) and completed payments
        (<code>payments.status = 'completed'</code>). Cancelled invoices and reversed
        payments are excluded from financial totals.
    </div>

    <div class="footer">
        <p>This is a computer-generated customer account statement from the Sales &amp; Inventory Management System.</p>
    </div>
</div>

</body>
</html>
