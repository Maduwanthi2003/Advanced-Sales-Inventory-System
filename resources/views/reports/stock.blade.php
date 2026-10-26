{{-- Stock report filters, summaries, and results. --}}
@extends('layouts.app')

@section('title', 'Stock Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stock-report.css') }}?v=1">
@endpush

@section('content')
<div class="st-page">



    {{-- FILTER BAR --}}
    <form action="{{ route('reports.stock') }}"
          method="GET"
          class="st-filters"
          style="grid-template-columns: repeat(4, minmax(0, 1fr)) auto;">

        <div class="st-field">
            <label for="st-category">Category</label>

            <select name="category_id" id="st-category">
                <option value="">All Categories</option>

                @foreach($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected((string)$categoryId === (string)$category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="st-field">
            <label for="st-supplier">Supplier</label>

            <select name="supplier_id" id="st-supplier">
                <option value="">All Suppliers</option>

                @foreach($suppliers ?? [] as $supplier)
                    <option value="{{ $supplier->id }}"
                        @selected((string)($supplierId ?? '') === (string)$supplier->id)>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="st-field">
            <label for="st-product">Product</label>

            <select name="product_id" id="st-product">
                <option value="">All Products</option>

                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                        @selected((string)$productId === (string)$product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="st-field">
            <label for="st-status">Stock Status</label>

            <select name="stock_status" id="st-status">
                <option value="">All Statuses</option>
                <option value="in_stock"
                    @selected($stockStatus === 'in_stock')>
                    In Stock
                </option>
                <option value="low_stock"
                    @selected($stockStatus === 'low_stock')>
                    Low Stock
                </option>
                <option value="out_of_stock"
                    @selected($stockStatus === 'out_of_stock')>
                    Out of Stock
                </option>
            </select>
        </div>

        <div class="st-filter-actions">
            <button type="submit" class="st-btn st-btn-primary">
                <svg viewBox="0 0 24 24">
                    <circle cx="10.5" cy="10.5" r="6.5"/>
                    <path d="m16 16 5 5"/>
                </svg>
                Generate Report
            </button>

            <a href="{{ route('reports.stock') }}"
               class="st-btn st-btn-reset">
                <svg viewBox="0 0 24 24">
                    <path d="M3 11a9 9 0 1 1 2 6M3 4v7h7"/>
                </svg>
                Reset
            </a>
        </div>
    </form>

    {{-- SUMMARY CARDS --}}
    <div class="st-summary">

        <article class="st-stat st-stat-blue">
            <div class="st-stat-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
                    <path d="m3 7 9 5 9-5M12 12v10"/>
                </svg>
            </div>
            <div>
                <div class="st-stat-label">Total Products &amp; Stock</div>
                <div class="st-stat-value">
                    {{ number_format($totalProducts) }}
                </div>
                <div class="st-stat-note">{{ number_format($totalStock) }} total units in stock</div>
            </div>
        </article>

        <article class="st-stat st-stat-green">
            <div class="st-stat-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M15 8c-2-2-6-1-6 1 0 3 6 1 6 5 0 3-5 4-7 1M12 5v14"/>
                </svg>
            </div>
            <div>
                <div class="st-stat-label">Inventory Valuation (Rs.)</div>
                <div class="st-stat-value">
                    {{ number_format($totalValuation ?? 0, 2) }}
                </div>
                <div class="st-stat-note">Quantity × Cost Price (all filtered items)</div>
            </div>
        </article>

        <article class="st-stat st-stat-red">
            <div class="st-stat-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3 2 21h20L12 3z"/>
                    <path d="M12 9v5M12 18h.01"/>
                </svg>
            </div>
            <div>
                <div class="st-stat-label">Low Stock Items</div>
                <div class="st-stat-value">
                    {{ number_format($lowStock) }}
                </div>
                <div class="st-stat-note">At or below reorder level (&gt; 0)</div>
            </div>
        </article>

        <article class="st-stat st-stat-yellow">
            <div class="st-stat-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="m9 9 6 6M15 9l-6 6"/>
                </svg>
            </div>
            <div>
                <div class="st-stat-label">Out of Stock Items</div>
                <div class="st-stat-value">
                    {{ number_format($outOfStock) }}
                </div>
                <div class="st-stat-note">Products with zero stock</div>
            </div>
        </article>

    </div>

    {{-- CHARTS --}}
    <div class="st-chart-grid">

        <section class="st-panel">
            <h2>Stock by Category</h2>

            <div class="st-category-chart">
                <div class="st-doughnut-wrap">
                    <canvas id="stockCategoryChart"></canvas>
                </div>

                <div id="stockCategoryLegend"
                     class="st-category-legend"></div>
            </div>
        </section>

        <section class="st-panel">
            <div class="st-chart-title">
                <h2>Stock Status Overview</h2>

                <div class="st-status-legend">
                    <span><i class="st-dot st-dot-blue"></i>In Stock</span>
                    <span><i class="st-dot st-dot-yellow"></i>Low Stock</span>
                    <span><i class="st-dot st-dot-red"></i>Out of Stock</span>
                </div>
            </div>

            <div class="st-bar-wrap">
                <canvas id="stockStatusChart"></canvas>
            </div>
        </section>

    </div>

    {{-- PRODUCT TABLE --}}
    <section class="st-panel st-table-panel">

        <div class="st-table-heading">
            <h2>Product Stock &amp; Valuation Details</h2>

            <div class="st-export-actions">
                <a href="{{ route('reports.stock', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="st-btn st-btn-pdf"
                   title="Open full filtered report for Save as PDF">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h6M9 17h6"/>
                    </svg>
                    Export PDF
                </a>

                <a href="{{ route('reports.stock.export', request()->query()) }}"
                   class="st-btn st-btn-excel">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 2h12v20H6zM9 9l6 6M15 9l-6 6"/>
                    </svg>
                    Export Excel
                </a>

                <a href="{{ route('reports.stock', array_merge(request()->query(), ['print' => 1])) }}"
                   target="_blank"
                   rel="noopener"
                   class="st-btn st-btn-print">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5h-2M6 14h12v7H6z"/>
                    </svg>
                    Print
                </a>
            </div>
        </div>

        <div class="st-table-scroll">
            <table class="st-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Quantity</th>
                        <th>Reorder Level</th>
                        <th>Cost Price (Rs.)</th>
                        <th>Selling Price (Rs.)</th>
                        <th>Valuation (Rs.)</th>
                        <th>Stock Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($stockRows as $product)
                        <tr>
                            <td>
                                {{ $stockRows->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <div class="st-product-image">
                                    @if($product['image'])
                                        <img src="{{ asset('storage/' . ltrim($product['image'], '/')) }}"
                                             alt="{{ $product['name'] }}"
                                             loading="lazy"
                                             onerror="this.hidden=true">
                                    @else
                                        <svg viewBox="0 0 24 24">
                                            <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
                                            <path d="m3 7 9 5 9-5M12 12v10"/>
                                        </svg>
                                    @endif
                                </div>
                            </td>

                            <td class="st-link-text">
                                {{ $product['sku'] }}
                            </td>

                            <td class="st-link-text">
                                {{ $product['name'] }}
                                @if(!$product['is_active'])
                                    <span style="font-size: 9px; color: #c84a53; font-weight: 700;">(Inactive)</span>
                                @endif
                            </td>

                            <td>{{ $product['category'] }}</td>
                            <td>{{ $product['supplier'] }}</td>

                            <td class="{{ $product['quantity'] <= 0 ? 'st-zero' : '' }}">
                                {{ number_format($product['quantity']) }}
                            </td>

                            <td>{{ number_format($product['reorder_level']) }}</td>

                            <td>{{ number_format($product['cost_price'], 2) }}</td>

                            <td>{{ number_format($product['selling_price'], 2) }}</td>

                            <td><strong>{{ number_format($product['valuation'], 2) }}</strong></td>

                            <td>
                                <span class="st-status st-status-{{ $product['status'] }}">
                                    {{ ucwords(str_replace('_', ' ', $product['status'])) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('products.index', ['search' => $product['sku']]) }}"
                                   class="st-view"
                                   title="Find product"
                                   aria-label="Find {{ $product['name'] }} in Products">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="st-empty">
                                No products found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="st-table-footer">
            <span>
                Showing {{ $stockRows->firstItem() ?? 0 }}
                to {{ $stockRows->lastItem() ?? 0 }}
                of {{ $stockRows->total() }} entries
            </span>

            @if(empty($isPrintMode))
                <div class="st-pagination">
                    {{ $stockRows->links() }}
                </div>
            @endif
        </div>

    </section>

</div>
@endsection

@push('scripts')
<script>
    window.stockReportData = {
        categories: @json($categoryChart),
        statuses: @json($statusChart)
    };
    @if(!empty($isPrintMode))
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 350);
    });
    @endif
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/stock-report.js') }}?v=1" defer></script>
@endpush
