{{-- Supplier list and management actions. --}}
@extends('layouts.app')

@section('title', 'Suppliers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/suppliers.css') }}?v=1">
@endpush

@section('content')

<div class="supplier-page">



    <section class="supplier-card">

        @if(session('success'))
            <div class="supplier-alert success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="supplier-alert error">
                {{ session('error') }}
            </div>
        @endif

        <div class="supplier-toolbar">
            <form action="{{ route('suppliers.index') }}"
                  method="GET"
                  class="supplier-search">

                <span>⌕</span>

                <input type="search"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search suppliers..."
                       aria-label="Search suppliers">

                <button type="submit">Search</button>
            </form>

            <a href="{{ route('suppliers.create') }}"
               class="supplier-primary-btn">
                ＋ Add Supplier
            </a>
        </div>

        <div class="supplier-table-wrap">
            <table class="supplier-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->list_serial ?? ($suppliers->firstItem() + $loop->index) }}</td>

                            <td class="supplier-name">
                                {{ $supplier->name }}
                            </td>

                            <td>{{ $supplier->company_name ?: '—' }}</td>
                            <td>{{ $supplier->contact_person ?: '—' }}</td>
                            <td>{{ $supplier->phone ?: '—' }}</td>
                            <td>{{ $supplier->email ?: '—' }}</td>
                            <td>{{ $supplier->address ?: '—' }}</td>
                            <td>{{ $supplier->city ?: '—' }}</td>

                            <td>
                                <span class="supplier-status {{ $supplier->is_active ? 'active' : 'inactive' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                <div class="supplier-actions">

                                    <a href="{{ route('suppliers.edit', $supplier) }}"
                                       class="supplier-edit"
                                       title="Edit Supplier"
                                       aria-label="Edit {{ $supplier->name }}">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('suppliers.destroy', $supplier) }}"
                                          method="POST"
                                          data-confirm-delete
                                          data-delete-type="supplier"
                                          data-delete-name="{{ $supplier->name }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="supplier-delete"
                                                title="Delete Supplier"
                                                aria-label="Delete {{ $supplier->name }}">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15M10 10v7M14 10v7"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="supplier-empty">
                                No suppliers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="supplier-pagination">
            <span>
                @if($suppliers->total() > 0)
                    Showing {{ $suppliers->firstItem() }}
                    to {{ $suppliers->lastItem() }}
                    of {{ $suppliers->total() }} entries
                @else
                    Showing 0 entries
                @endif
            </span>

            <div class="supplier-pagination-links">
                {{ $suppliers->links() }}
            </div>
        </div>

    </section>

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
