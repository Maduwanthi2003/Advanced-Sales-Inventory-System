{{-- Invoice details, items, and payment summary. --}}
@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/invoices.css') }}?v=1">
@endpush


@section('content')
@php
    $canEditInvoice = auth()->user()?->hasPermission('invoices.edit') ?? false;
    $paidCents = (int) round(
        (float) $invoice->payments->where('status', 'completed')->sum('amount') * 100
    );
    $grandTotalCents = (int) round((float) $invoice->grand_total * 100);
    $outstandingCents = max(0, $grandTotalCents - $paidCents);
@endphp
<div class="iv-page">


    @if(session('success'))
        <div class="iv-alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="iv-alert iv-alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="iv-actions">

    <a href="{{ route('invoices.print', $invoice) }}"
       target="_blank"
       class="iv-btn iv-btn-light">
        Print Invoice
    </a>

    @if (
        $canEditInvoice
        && $invoice->status !== 'cancelled'
        && $invoice->payment_status === 'unpaid'
        && $invoice->payments->isEmpty()
    )
        <a href="{{ route('invoices.edit', $invoice) }}"
           class="iv-btn iv-btn-primary">
            Edit Invoice
        </a>

        <form method="POST"
              action="{{ route('invoices.cancel', $invoice) }}"
              onsubmit="return confirm('Cancel this invoice and restore stock?');">

            @csrf

            <button type="submit" class="iv-btn iv-btn-light">
                Cancel Invoice
            </button>
        </form>
    @endif

</div>

    <section class="iv-card iv-form-card">
        <div class="iv-section-row">
            <div class="iv-section-heading">
                <h2>{{ $invoice->invoice_number }}</h2>
                <p>Sales Invoice Information</p>
            </div>

            @if($invoice->status === 'cancelled')
                <span class="iv-status iv-unpaid">
                    Cancelled
                </span>
            @else
                <span class="iv-status iv-{{ $invoice->payment_status }}">
                    {{ ucfirst($invoice->payment_status) }}
                </span>
            @endif
        </div>

        <div class="iv-detail-grid">
            <div>
                <span>Customer</span>
                <strong>{{ $invoice->customer?->name ?? '—' }}</strong>
            </div>

            <div>
                <span>Sales Person</span>
                <strong>{{ $invoice->user?->name ?? '—' }}</strong>
            </div>

            <div>
                <span>Invoice Date</span>
                <strong>{{ $invoice->invoice_date->format('Y-m-d') }}</strong>
            </div>

            <div>
                <span>Due Date</span>
                <strong>
                    {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}
                </strong>
            </div>

            <div>
                <span>Payment Status</span>
                <strong>
                    {{ $invoice->status === 'cancelled' ? 'Cancelled' : ucfirst($invoice->payment_status) }}
                </strong>
            </div>

            <div>
                <span>Remarks</span>
                <strong>{{ $invoice->notes ?: '—' }}</strong>
            </div>
        </div>
    </section>

    <section class="iv-card iv-form-card">
        <div class="iv-section-row">
            <div class="iv-section-heading">
                <h2>Invoice Items</h2>
                <p>Products included in this invoice.</p>
            </div>
        </div>

        <div class="iv-table-scroll">
            <table class="iv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Unit Price (Rs.)</th>
                        <th>Quantity</th>
                        <th>Subtotal (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product?->name ?? '—' }}</td>
                            <td>{{ $item->product?->sku ?? '—' }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="iv-totals">
            <div>
                <span>Subtotal (Rs.)</span>
                <strong>{{ number_format($invoice->subtotal, 2) }}</strong>
            </div>
            <div>
                <span>Discount (Rs.)</span>
                <strong>{{ number_format($invoice->discount, 2) }}</strong>
            </div>
            <div>
                <span>Tax (Rs.)</span>
                <strong>{{ number_format($invoice->tax, 2) }}</strong>
            </div>
            <div>
                <span>Total Items</span>
                <strong>{{ $invoice->items->sum('quantity') }}</strong>
            </div>
            <div class="iv-grand-total">
                <span>Total Amount (Rs.)</span>
                <strong>{{ number_format($invoice->grand_total, 2) }}</strong>
            </div>
            <div>
                <span>Paid Amount (Rs.)</span>
                <strong>{{ number_format($paidCents / 100, 2) }}</strong>
            </div>
            <div>
                <span>Outstanding Balance (Rs.)</span>
                <strong>{{ number_format($outstandingCents / 100, 2) }}</strong>
            </div>
        </div>

        <div class="iv-form-actions">
            <a href="{{ route('invoices.index') }}"
               class="iv-cancel-btn">
                Back to Invoices
            </a>

            <a href="{{ route('invoices.print', $invoice) }}"
               target="_blank"
               class="iv-primary-btn">
                Print Invoice
            </a>
        </div>
    </section>
</div>
@endsection
