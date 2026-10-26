
{{-- Purchase details and line items. --}}
@extends('layouts.app')

@section('title', 'Purchase Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}?v=1">
@endpush

@section('content')
<div class="pu-page">


    @if(session('success'))
        <div class="pu-alert pu-success">{{ session('success') }}</div>
    @endif

    <section class="pu-card pu-section">
        <div class="pu-section-heading">
            <h2>{{ $purchase->purchase_number }}</h2>
            <span class="pu-status pu-{{ $purchase->status }}">
                {{ ucfirst($purchase->status) }}
            </span>
        </div>

        <div class="pu-detail-grid">
            <div><strong>Supplier</strong><span>{{ $purchase->supplier?->name }}</span></div>
            <div><strong>Date</strong><span>{{ $purchase->purchase_date->format('d M Y') }}</span></div>
            <div><strong>Created By</strong><span>{{ $purchase->user?->name }}</span></div>
            <div><strong>Warehouse</strong><span>Main Warehouse</span></div>
        </div>

        @if($purchase->notes)
            <p class="pu-remarks"><strong>Remarks:</strong> {{ $purchase->notes }}</p>
        @endif
    </section>

    <section class="pu-card pu-section">
        <h2>Purchase Items</h2>

        <div class="pu-table-scroll">
            <table class="pu-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Unit Cost (Rs.)</th>
                        <th>Quantity</th>
                        <th>Subtotal (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product?->name ?? '—' }}</td>
                            <td>{{ $item->product?->sku ?? '—' }}</td>
                            <td>{{ number_format($item->unit_cost, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pu-totals">
            <div class="pu-grand">
                <span>Total Amount (Rs.)</span>
                <strong>{{ number_format($purchase->total_amount, 2) }}</strong>
            </div>
        </div>

        <div class="pu-form-actions">
            <a href="{{ route('purchases.index') }}" class="pu-cancel">
                Back to Purchases
            </a>
        </div>
    </section>
</div>
@endsection
