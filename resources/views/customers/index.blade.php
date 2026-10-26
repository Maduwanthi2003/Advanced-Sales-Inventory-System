{{-- Customer list, filters, and management actions. --}}
@extends('layouts.app')

@section('title', 'Customers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customers.css') }}?v=1">
@endpush

@section('content')

<div class="customer-page">



    <section class="customer-panel">

        @if(session('success'))
            <div class="customer-alert customer-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="customer-alert customer-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="customer-toolbar">
            <form method="GET"
                  action="{{ route('customers.index') }}"
                  class="customer-search-form">

                <span class="customer-search-icon">⌕</span>

                <input type="search"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search customers..."
                       aria-label="Search customers">

                <button type="submit">Search</button>
            </form>

            <a href="{{ route('customers.create') }}"
               class="customer-primary-btn">
                <span>＋</span> Add Customer
            </a>
        </div>

        <div class="customer-table-wrap">
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                {{ $customer->list_serial ?? ($customers->firstItem() + $loop->index) }}
                            </td>

                            <td class="customer-name">
                                {{ $customer->name }}
                            </td>

                            <td>
                                {{ $customer->contact_person ?: '—' }}
                            </td>

                            <td>
                                {{ $customer->phone ?: '—' }}
                            </td>

                            <td>
                                {{ $customer->email ?: '—' }}
                            </td>

                            <td>
                                <span class="customer-status {{ $customer->is_active ? 'active' : 'inactive' }}">
                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                <div class="customer-actions">

                                    @if(auth()->user()?->hasRole('Admin') || (auth()->user()?->hasPermission('customers.view') && auth()->user()?->hasPermission('invoices.view') && auth()->user()?->hasPermission('payments.view')))
                                        <a href="{{ route('customers.statement', $customer) }}"
                                           class="customer-edit-btn"
                                           aria-label="View Statement for {{ $customer->name }}"
                                           title="View Statement">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M6 2h9l4 4v16H6z"/>
                                                <path d="M15 2v5h4M9 13h6M9 17h6"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <a href="{{ route('customers.edit', $customer) }}"
                                       class="customer-edit-btn"
                                       aria-label="Edit {{ $customer->name }}"
                                       title="Edit Customer">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('customers.destroy', $customer) }}"
                                          method="POST"
                                          data-confirm-delete
                                          data-delete-type="customer"
                                          data-delete-name="{{ $customer->name }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="customer-delete-btn"
                                                aria-label="Delete {{ $customer->name }}"
                                                title="Delete Customer">
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
                            <td colspan="7" class="customer-empty">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="customer-pagination">
            <p>
                @if($customers->total() > 0)
                    Showing {{ $customers->firstItem() }}
                    to {{ $customers->lastItem() }}
                    of {{ $customers->total() }} entries
                @else
                    Showing 0 entries
                @endif
            </p>

            <div class="customer-page-links">
                {{ $customers->links() }}
            </div>
        </div>

    </section>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
