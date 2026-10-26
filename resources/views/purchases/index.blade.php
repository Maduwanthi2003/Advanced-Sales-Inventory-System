
{{-- Purchase list and request filters. --}}
@extends('layouts.app')

@section('title', 'Purchases')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}?v=1">
@endpush

@section('content')
<div class="pu-page">


    <section class="pu-card">
        @if(session('success'))
            <div class="pu-alert pu-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="pu-alert pu-error">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('purchases.index') }}"
              class="pu-toolbar">
            <div class="pu-filter pu-filter-search">
                <label for="purchase_search">Search Purchases</label>
                <input type="search" id="purchase_search" name="search"
                       value="{{ $search }}"
                       placeholder="Search purchases..."
                       aria-label="Search purchases">
            </div>

            <div class="pu-filter">
                <label for="from_date">From Date</label>
                <input type="date" id="from_date" name="from_date"
                       value="{{ request('from_date') }}">
            </div>

            <div class="pu-filter">
                <label for="to_date">To Date</label>
                <input type="date" id="to_date" name="to_date"
                       value="{{ request('to_date') }}">
            </div>

            <div class="pu-filter">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}"
                            @selected((string)request('supplier_id') === (string)$supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pu-filter">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    @foreach(['completed', 'received', 'partial', 'cancelled'] as $status)
                        <option value="{{ $status }}"
                            @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pu-toolbar-actions">
                <button type="submit" class="pu-filter-btn">Filter</button>

                <a href="{{ route('purchases.create') }}" class="pu-add">
                    ＋ Add Purchase
                </a>
            </div>
        </form>

        <div class="pu-table-wrap">
            <table class="pu-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purchase No</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Total (Rs.)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->list_serial ?? ($purchases->firstItem() + $loop->index) }}</td>
                            <td class="pu-number">{{ $purchase->purchase_number }}</td>
                            <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                            <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td>{{ number_format($purchase->total_amount, 2) }}</td>
                            <td>
                                <span class="pu-status pu-{{ $purchase->status }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="pu-actions">
                                    <a href="{{ route('purchases.show', $purchase) }}"
                                       title="View purchase"
                                       aria-label="View {{ $purchase->purchase_number }}">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="pu-empty">
                                No purchases found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pu-footer">
            <span>
                @if($purchases->total())
                    Showing {{ $purchases->firstItem() }}
                    to {{ $purchases->lastItem() }}
                    of {{ $purchases->total() }} entries
                @else
                    Showing 0 entries
                @endif
            </span>

            <nav class="pu-pages" aria-label="Purchase pages">
                @if($purchases->onFirstPage())
                    <span class="pu-page-link pu-disabled">‹</span>
                @else
                    <a class="pu-page-link" href="{{ $purchases->previousPageUrl() }}">‹</a>
                @endif

                @foreach($purchases->getUrlRange(
                    max(1, $purchases->currentPage() - 2),
                    min($purchases->lastPage(), $purchases->currentPage() + 2)
                ) as $page => $url)
                    <a href="{{ $url }}"
                       class="pu-page-link {{ $page == $purchases->currentPage() ? 'pu-current' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach

                @if($purchases->hasMorePages())
                    <a class="pu-page-link" href="{{ $purchases->nextPageUrl() }}">›</a>
                @else
                    <span class="pu-page-link pu-disabled">›</span>
                @endif
            </nav>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
