{{-- Dashboard summaries and charts. --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/dashboard.css') }}?v=3">
@endpush

@section('content')




    <!-- Statistics Cards -->

    <section class="dash-stats">

        @if($canSeeSales)
            <!-- Total Sales -->
            <article class="dash-stat stat-sales">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 20V10h4v10M10 20V4h4v16M16 20v-8h4v8"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Total Sales</p>
                <strong>
                    Rs. {{ number_format($totalSales ?? 0, 2) }}
                </strong>
                <small>Active invoice sales</small>
            </article>

            <!-- Today's Sales -->
            <article class="dash-stat stat-purchases">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Today's Sales</p>
                <strong>
                    Rs. {{ number_format($todaySales ?? 0, 2) }}
                </strong>
                <small>Sales recorded today</small>
            </article>
        @endif

        @if($canSeePayments)
            <!-- Outstanding Payments -->
            <article class="dash-stat stat-low">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Outstanding Payments</p>
                <strong>
                    Rs. {{ number_format($outstandingPayments ?? 0, 2) }}
                </strong>
                <small>Unpaid &amp; partial invoice balance</small>
            </article>
        @endif

        @if($canSeePurchases)
            <!-- Total Purchases -->
            <article class="dash-stat stat-purchases">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="7" width="18" height="14" rx="2"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Total Purchases</p>
                <strong>
                    Rs. {{ number_format($totalPurchases ?? 0, 2) }}
                </strong>
                <small>Recorded purchases</small>
            </article>
        @endif

        @if($canSeeCustomers)
            <!-- Total Customers -->
            <article class="dash-stat stat-sales">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Total Customers</p>
                <strong>
                    {{ number_format($totalCustomers ?? 0) }}
                </strong>
                <small>Registered customers</small>
            </article>
        @endif

        @if($canSeeSuppliers)
            <!-- Total Suppliers -->
            <article class="dash-stat stat-sales">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Total Suppliers</p>
                <strong>
                    {{ number_format($totalSuppliers ?? 0) }}
                </strong>
                <small>Registered suppliers</small>
            </article>
        @endif

        @if($canSeeProducts)
            <!-- Total Products -->
            <article class="dash-stat stat-products">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m12 2 9 5v10l-9 5-9-5V7zM3 7l9 5 9-5M12 12v10"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Total Products</p>
                <strong>
                    {{ number_format($totalProducts ?? 0) }}
                </strong>
                <small>Products in catalog</small>
            </article>
        @endif

        @if($canSeeInventoryStock)
            <!-- Low Stock Products -->
            <article class="dash-stat stat-low">
                <div class="stat-top">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m12 3 10 18H2L12 3Z"/>
                        <path d="M12 9v5M12 18h.01"/>
                    </svg>
                    <span>•••</span>
                </div>
                <p>Low Stock Products</p>
                <strong>
                    {{ number_format($lowStock ?? 0) }}
                </strong>
                <small>At or below reorder level</small>
            </article>
        @endif

    </section>


    <!-- Dashboard Charts -->

    @if($canSeeSales || $canSeeInventoryStock)
        <section class="dash-charts"
                 @if(!$canSeeSales || !$canSeeInventoryStock)
                     style="grid-template-columns: 1fr;"
                 @endif>

            @if($canSeeSales)
                <!-- Sales Overview -->
                <article class="dash-panel sales-panel">
                    <div class="panel-heading">
                        <h2>Sales Overview</h2>
                        <span class="year-tag">
                            {{ $currentYear }}
                        </span>
                    </div>

                    <div class="sales-chart-wrap">
                        <canvas
                            id="salesChart"
                            role="img"
                            aria-label="Monthly sales chart for {{ $currentYear }}">
                        </canvas>
                    </div>
                </article>
            @endif

            @if($canSeeInventoryStock)
                <!-- Stock Status -->
                <article class="dash-panel stock-panel">
                    <div class="panel-heading">
                        <h2>Stock Status</h2>
                    </div>

                    <div class="stock-content">
                        <!-- Doughnut Chart -->
                        <div class="stock-donut" id="stockDonut">
                            <div class="donut-center">
                                <strong>
                                    {{ number_format($totalProducts ?? 0) }}
                               </strong>
                                <span>Total Products</span>
                            </div>
                        </div>

                        <!-- Stock Legend -->
                        <div class="stock-legend">
                            <div>
                                <i class="legend-dot dot-green"></i>
                                <span>In Stock</span>
                                <strong>{{ $stockPercent['inStock'] ?? 0 }}%</strong>
                            </div>
                            <div>
                                <i class="legend-dot dot-blue"></i>
                                <span>Low Stock</span>
                                <strong>{{ $stockPercent['lowStock'] ?? 0 }}%</strong>
                            </div>
                            <div>
                                <i class="legend-dot dot-orange"></i>
                                <span>Out of Stock</span>
                                <strong>{{ $stockPercent['outOfStock'] ?? 0 }}%</strong>
                            </div>
                            <div>
                                <i class="legend-dot dot-red"></i>
                                <span>Inactive</span>
                                <strong>{{ $stockPercent['inactive'] ?? 0 }}%</strong>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

        </section>
    @endif


    <!-- Recent Invoices (Admin & Sales User) -->

    @if($canSeeSales)
        <section class="dash-panel recent-panel" @if($canSeeInventoryStock) style="margin-bottom: 14px;" @endif>

            <div class="panel-heading">
                <h2>Recent Invoices</h2>

                @if($canCreateInvoice && Route::has('invoices.create'))
                    <a href="{{ route('invoices.create') }}"
                       class="create-invoice-btn">
                        + Create Invoice
                    </a>
                @endif
            </div>

            <div class="dash-table-scroll">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total (Rs.)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer?->name ?? 'Unknown' }}</td>
                                <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                <td>{{ number_format($invoice->grand_total, 2) }}</td>
                                <td>
                                    <span class="status-badge status-{{ $invoice->payment_status }}">
                                        {{ ucfirst($invoice->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    @if(Route::has('invoices.show'))
                                        <a href="{{ route('invoices.show', $invoice) }}"
                                           class="view-invoice">
                                            View
                                        </a>
                                    @else
                                        <span class="action-unavailable">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    No sales invoices recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </section>
    @endif


    <!-- Low Stock Products Table (Admin & Inventory User) -->

    @if($canSeeInventoryStock)
        <section class="dash-panel recent-panel">

            <div class="panel-heading">
                <h2>Low Stock Products</h2>

                @if(Route::has('products.index'))
                    <a href="{{ route('products.index', ['status' => 'low_stock']) }}"
                       class="create-invoice-btn">
                        View All
                    </a>
                @endif
            </div>

            <div class="dash-table-scroll">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($lowStockProducts as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ $product->reorder_level }}</td>
                                <td>
                                    @if($product->quantity == 0)
                                        <span class="status-badge status-unpaid">
                                            Out of Stock
                                        </span>
                                    @else
                                        <span class="status-badge status-partial">
                                            Low Stock
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    All active products are above their reorder levels.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </section>
    @endif

@endsection


@push('scripts')
    @php
        $dashboardMonthlySales = $salesChart ?? array_fill(0, 12, 0);
        $dashboardStock = $stockPercent ?? [
            'inStock' => 0,
            'lowStock' => 0,
            'outOfStock' => 0,
            'inactive' => 0,
        ];
    @endphp

    <script>
        window.webAdminDashboard = {
            monthlySales: @json($dashboardMonthlySales),
            stock: @json($dashboardStock),
            totalProducts: @json($totalProducts ?? 0)
        };
    </script>

    <script
        src="{{ asset('js/dashboard.js') }}?v=3"
        defer>
    </script>

@endpush
