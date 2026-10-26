{{-- Printable invoice details. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('laravel-icon.svg') }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef2f7;
            color: #24364b;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .toolbar {
            max-width: 850px;
            margin: 20px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .toolbar button {
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            background: #24618d;
            color: white;
            cursor: pointer;
        }

        .invoice-sheet {
            max-width: 850px;
            min-height: 1050px;
            margin: 0 auto 30px;
            padding: 45px;
            background: white;
            box-shadow: 0 4px 22px rgba(0,0,0,.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 3px solid #24618d;
            padding-bottom: 25px;
        }

        .header h1 {
            font-size: 32px;
            color: #194a70;
            margin: 0;
        }

        .header p { margin: 8px 0; }

        .status {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background: #e8eff8;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .details {
            display: flex;
            justify-content: space-between;
            gap: 25px;
            margin: 35px 0;
        }

        .details > div { flex: 1; }

        .details h3 {
            color: #194a70;
            margin-bottom: 12px;
        }

        .details p { margin: 7px 0; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #194a70;
            color: white;
            padding: 13px;
            text-align: left;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #e4eaf1;
        }

        .right { text-align: right; }

        .totals {
            width: 320px;
            margin: 30px 0 0 auto;
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .grand-total {
            border-top: 2px solid #194a70;
            font-size: 18px;
            font-weight: bold;
            color: #194a70;
        }

        .footer {
            margin-top: 70px;
            text-align: center;
            color: #718096;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        @media screen and (max-width: 640px) {
            .toolbar {
                padding: 0 12px;
                justify-content: stretch;
            }

            .toolbar button {
                width: 100%;
            }

            .invoice-sheet {
                min-height: auto;
                margin: 0 10px 20px;
                padding: 20px 14px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .header .right {
                text-align: left;
            }

            .header h1 {
                font-size: 24px;
            }

            .details {
                flex-direction: column;
                gap: 16px;
                margin: 22px 0;
            }

            .totals {
                width: 100%;
                max-width: 100%;
            }
        }

        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .invoice-sheet {
                width: 100%;
                min-height: auto;
                padding: 0;
                margin: 0;
                box-shadow: none;
            }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    </style>
    <link rel="stylesheet"
          href="{{ asset('css/button-system.css') }}?v=1">
</head>
<body>
@php
    $paidCents = (int) round(
        (float) $invoice->payments->where('status', 'completed')->sum('amount') * 100
    );
    $grandTotalCents = (int) round((float) $invoice->grand_total * 100);
    $outstandingCents = max(0, $grandTotalCents - $paidCents);
@endphp

<div class="toolbar">
    <button type="button" onclick="window.print()">
        Print Invoice
    </button>
</div>

<div class="invoice-sheet">
    <div class="header">
        <div>
            <h1>SALES INVOICE</h1>
            <p>{{ $invoice->invoice_number }}</p>

            <span class="status {{ $invoice->status === 'cancelled' ? 'cancelled' : '' }}">
                {{ $invoice->status === 'cancelled'
                    ? 'Cancelled'
                    : $invoice->payment_status }}
            </span>
        </div>

        <div class="right">
            <h2>Sales & Inventory System</h2>
            <p>Official Sales Invoice</p>
        </div>
    </div>

    <div class="details">
        <div>
            <h3>Bill To</h3>
            <p><strong>{{ $invoice->customer->name }}</strong></p>
            <p>{{ $invoice->customer->email ?? '' }}</p>
            <p>{{ $invoice->customer->phone ?? '' }}</p>
        </div>

        <div>
            <h3>Invoice Details</h3>
            <p><strong>Invoice No:</strong>
                {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong>
                {{ $invoice->invoice_date->format('d M Y') }}</p>
            <p><strong>Due Date:</strong>
                {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
            <p><strong>Sales Person:</strong>
                {{ $invoice->user->name ?? '—' }}</p>
        </div>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product->name ?? 'Product' }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">
                            {{ number_format($item->unit_price, 2) }}
                        </td>
                        <td class="right">
                            {{ number_format($item->total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div>
            <span>Subtotal</span>
            <span>Rs. {{ number_format($invoice->subtotal, 2) }}</span>
        </div>

        <div>
            <span>Discount</span>
            <span>Rs. {{ number_format($invoice->discount, 2) }}</span>
        </div>

        <div>
            <span>Tax</span>
            <span>Rs. {{ number_format($invoice->tax, 2) }}</span>
        </div>

        <div class="grand-total">
            <span>Grand Total</span>
            <span>Rs. {{ number_format($invoice->grand_total, 2) }}</span>
        </div>

        <div>
            <span>Paid Amount</span>
            <span>Rs. {{ number_format($paidCents / 100, 2) }}</span>
        </div>

        <div>
            <span>Balance Due</span>
            <span>Rs. {{ number_format($outstandingCents / 100, 2) }}</span>
        </div>
    </div>

    @if ($invoice->notes)
        <div>
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated invoice.</p>
    </div>
</div>

</body>
</html>
