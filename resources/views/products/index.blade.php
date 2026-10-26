{{-- Product list and inventory filters. --}}
@extends('layouts.app')

@section('title', 'Products')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/products.css') }}?v=1">
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $canCreateProduct = $authUser?->hasPermission('products.create') ?? false;
    $canEditProduct = $authUser?->hasPermission('products.edit') ?? false;
    $canDeleteProduct = $authUser?->hasPermission('products.delete') ?? false;
    $showProductActions = $canEditProduct || $canDeleteProduct;
@endphp
<div class="pr-page">


    <section class="pr-card">
        @if(session('success'))
            <div class="pr-alert pr-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="pr-alert pr-error">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('products.index') }}"
              class="pr-toolbar" id="productFilterForm">

            <div class="pr-search">
                <svg viewBox="0 0 24 24">
                    <circle cx="10.5" cy="10.5" r="6.5"/>
                    <path d="m16 16 5 5"/>
                </svg>
                <input type="search" name="search"
                       value="{{ $search }}"
                       placeholder="Search products..."
                       aria-label="Search products">
            </div>

            <div class="pr-filter">
                <label for="categoryFilter">Category</label>
                <select id="categoryFilter" name="category_id"
                        onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected((string)$categoryId === (string)$category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pr-filter">
                <label for="statusFilter">Status</label>
                <select id="statusFilter" name="status"
                        onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="in_stock" @selected($status === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected($status === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected($status === 'out_of_stock')>Out of Stock</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
            </div>

            <button type="submit" class="pr-filter-submit">Search</button>

            @if($canCreateProduct)
                <a href="{{ route('products.create') }}" class="pr-add">
                    <span>＋</span> Add Product
                </a>
            @endif
        </form>

        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Price (Rs.)</th>
                        <th>Stock</th>
                        <th>Status</th>
                        @if($showProductActions)
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->list_serial ?? ($products->firstItem() + $loop->index) }}</td>
                            <td>
                                @if($product->image_path)
                                    <img class="pr-thumbnail"
                                         src="{{ request()->getBaseUrl() . '/storage/' . ltrim($product->image_path, '/') }}"
                                         alt="{{ $product->name }}">
                                @else
                                    <span class="pr-no-image">
                                        <svg viewBox="0 0 24 24">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8" cy="8" r="2"/>
                                            <path d="m3 18 6-6 4 4 3-3 5 5"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                            <td class="pr-name">{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ number_format($product->selling_price, 2) }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td>
                                @if(!$product->is_active)
                                    <span class="pr-status pr-inactive">Inactive</span>
                                @elseif($product->quantity == 0)
                                    <span class="pr-status pr-out">Out of Stock</span>
                                @elseif($product->quantity <= $product->reorder_level)
                                    <span class="pr-status pr-low">Low Stock</span>
                                @else
                                    <span class="pr-status pr-stock">In Stock</span>
                                @endif
                            </td>
                            @if($showProductActions)
                                <td>
                                    <div class="pr-actions">
                                        @if($canEditProduct)
                                            <a href="{{ route('products.edit', $product) }}"
                                               class="pr-edit"
                                               title="Edit product"
                                               aria-label="Edit {{ $product->name }}">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M12 20h9"/>
                                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                                </svg>
                                            </a>
                                        @endif
                                        @if($canDeleteProduct)
                                            <form method="POST"
                                                  action="{{ route('products.destroy', $product) }}"
                                                  data-confirm-delete
                                                  data-delete-type="product"
                                                  data-delete-name="{{ $product->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="pr-delete"
                                                        title="Delete product"
                                                        aria-label="Delete {{ $product->name }}">
                                                    <svg viewBox="0 0 24 24">
                                                        <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15M10 10v7M14 10v7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showProductActions ? 9 : 8 }}" class="pr-empty">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pr-footer">
            <span>
                @if($products->total())
                    Showing {{ $products->firstItem() }} to
                    {{ $products->lastItem() }} of
                    {{ $products->total() }} entries
                @else
                    Showing 0 entries
                @endif
            </span>
            <nav class="pr-pagination" aria-label="Product pages">
                @if($products->onFirstPage())
                    <span class="pr-page-link pr-disabled">‹</span>
                @else
                    <a class="pr-page-link" href="{{ $products->previousPageUrl() }}">‹</a>
                @endif

                @foreach($products->getUrlRange(
                    max(1, $products->currentPage() - 2),
                    min($products->lastPage(), $products->currentPage() + 2)
                ) as $page => $url)
                    <a href="{{ $url }}"
                       class="pr-page-link {{ $page === $products->currentPage() ? 'pr-current' : '' }}"
                       @if($page === $products->currentPage()) aria-current="page" @endif>
                        {{ $page }}
                    </a>
                @endforeach

                @if($products->hasMorePages())
                    <a class="pr-page-link" href="{{ $products->nextPageUrl() }}">›</a>
                @else
                    <span class="pr-page-link pr-disabled">›</span>
                @endif
            </nav>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
