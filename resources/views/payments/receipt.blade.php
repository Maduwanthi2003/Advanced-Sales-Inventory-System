{{-- Printable payment receipt. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt - {{ $payment->payment_number }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('laravel-icon.svg') }}">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef3f9;
            color: #1c3552;
            font-size: 14px;
        }

        .toolbar {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            justify-content: flex-end;
        }

        .print-btn {
            border: none;
            background: #0862f5;
            color: #fff;
            border-radius: 6px;
            padding: 12px 22px;
            cursor: pointer;
            font-weight: 600;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto 30px;
            background: #fff;
            padding: 45px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 3px solid #0862f5;
            padding-bottom: 25px;
        }

        h1 {
            color: #123b7b;
            margin: 0 0 10px;
            font-size: 28px;
        }

        h2 {
            color: #123b7b;
            font-size: 16px;
            margin: 30px 0 15px;
        }

        .muted { color: #71829a; }

        .right { text-align: right; }

        .status {
            display: inline-block;
            margin-top: 12px;
            padding: 7px 13px;
            border-radius: 5px;
            background: #e0f7e9;
            color: #157347;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-reversed {
            background: #ffe4e4;
            color: #b91c1c;
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 25px;
        }

        .details p {
            margin: 10px 0;
            line-height: 1.5;
        }

        .amount-box {
            margin-top: 30px;
            background: #edf5ff;
            padding: 24px;
            text-align: center;
            border-radius: 8px;
        }

        .amount-box strong {
            display: block;
            font-size: 30px;
            color: #0757d6;
            margin-top: 10px;
        }

        .notes {
            margin-top: 25px;
            padding: 15px;
            border: 1px solid #e3eaf4;
            border-radius: 6px;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e3eaf4;
            text-align: center;
            color: #71829a;
        }

        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .receipt {
                max-width: none;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        @media (max-width: 600px) {
            .receipt { padding: 22px; }
            .receipt-header { flex-direction: column; }
            .details { grid-template-columns: 1fr; }
            .right { text-align: left; }
        }
    </style>
    <link rel="stylesheet"
          href="{{ asset('css/button-system.css') }}?v=1">
</head>
<body>

<div class="toolbar">
    <button class="print-btn" onclick="window.print()">
        Print Receipt
    </button>
</div>

<div class="receipt">

    <div class="receipt-header">
        <div>
            <h1>PAYMENT RECEIPT</h1>
            <p class="muted">
                {{ $payment->payment_number ?? 'Payment #'.$payment->id }}
            </p>

            <span class="status {{ $payment->status === 'reversed' ? 'status-reversed' : '' }}">
                {{ ucfirst($payment->status) }}
            </span>
        </div>

        <div class="right">
            <h2>Sales & Inventory System</h2>
            <p class="muted">Payment Transaction Record</p>
        </div>
    </div>

    <div class="details">
        <div>
            <h2>Received From</h2>
            <p>
                <strong>
                    {{ $payment->invoice?->customer?->name ?? '—' }}
                </strong>
            </p>
            <p>{{ $payment->invoice?->customer?->email ?? '' }}</p>
        </div>

        <div>
            <h2>Payment Information</h2>

            <p><strong>Receipt No:</strong>
                {{ $payment->payment_number ?? '—' }}</p>

            <p><strong>Invoice No:</strong>
                {{ $payment->invoice?->invoice_number ?? '—' }}</p>

            <p><strong>Date:</strong>
                {{ $payment->payment_date?->format('d M Y') ?? '—' }}</p>

            <p><strong>Method:</strong>
                {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</p>

            <p><strong>Reference:</strong>
                {{ $payment->reference_number ?? '—' }}</p>

            <p><strong>Recorded By:</strong>
                {{ $payment->user?->name ?? '—' }}</p>
        </div>
    </div>

    <div class="amount-box">
        <span>Payment Amount</span>
        <strong>Rs. {{ number_format($payment->amount, 2) }}</strong>
    </div>

    @if($payment->status === 'reversed')
        <div class="notes">
            <strong>REVERSED PAYMENT — NOT VALID AS PROOF OF PAYMENT</strong>
            <p>Reversed At: {{ $payment->reversed_at?->format('d M Y H:i') ?? '—' }}</p>
            <p>Reason: {{ $payment->reversal_reason ?? '—' }}</p>
        </div>
    @endif

    @if($payment->notes)
        <div class="notes">
            <strong>Remarks</strong>
            <p>{{ $payment->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your payment.</p>
        <p>This receipt is generated by the Sales & Inventory System.</p>
    </div>

</div>
</body>
</html>
