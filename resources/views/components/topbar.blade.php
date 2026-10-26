{{-- Prepare the authenticated user's topbar details. --}}
@php
    $routeName = request()->route()?->getName() ?? '';

    $routeTitleMap = [
        'dashboard'                        => 'Dashboard',
        'customers.statement'              => 'Customer Statement',
        'customers.statement.print'        => 'Customer Statement',
        'customers.index'                  => 'Customers',
        'customers.create'                 => 'Add Customer',
        'customers.edit'                   => 'Edit Customer',
        'suppliers.index'                  => 'Suppliers',
        'suppliers.create'                 => 'Add Supplier',
        'suppliers.edit'                   => 'Edit Supplier',
        'categories.index'                 => 'Categories',
        'categories.create'                => 'Add Category',
        'categories.edit'                   => 'Edit Category',
        'products.index'                   => 'Products',
        'products.create'                  => 'Add Product',
        'products.edit'                    => 'Edit Product',
        'purchases.index'                  => 'Purchases',
        'purchases.create'                 => 'New Purchase',
        'purchases.show'                   => 'Purchase Details',
        'invoices.index'                   => 'Invoices',
        'invoices.create'                  => 'Create Invoice',
        'invoices.show'                    => 'Invoice Details',
        'invoices.edit'                    => 'Edit Invoice',
        'payments.index'                   => 'Payments',
        'payments.create'                  => 'Record Payment',
        'payments.show'                    => 'Payment Details',
        'reports.sales'                    => 'Sales Report',
        'reports.purchases'                => 'Purchase Report',
        'reports.stock'                    => 'Stock Report',
        'reports.customer-outstanding'     => 'Customer Outstanding Report',
        'users.index'                      => 'Users',
        'users.create'                     => 'Create User',
        'users.show'                       => 'User Details',
        'users.edit'                       => 'Edit User',
        'roles.index'                      => 'Roles & Permissions',
        'roles.create'                     => 'Create Role',
        'roles.edit'                       => 'Edit Role',
    ];

    $yieldedTitle = trim($__env->yieldContent('page_title')) ?: trim($__env->yieldContent('title'));
    $pageTitle = $routeTitleMap[$routeName]
        ?? ($yieldedTitle !== '' ? $yieldedTitle : 'Dashboard');

    $authUser = auth()->user();
    $userName = $authUser?->name ?? 'Admin';
    $userProfileImage = $authUser?->profile_image_url;
    $userEmail = $authUser?->email ?? '';
    $userRole = $authUser?->role?->name ?? 'User';
@endphp

<header class="wa-topbar">

    <div class="wa-topbar-left">
        <button type="button"
                class="wa-menu-button"
                id="sidebarToggle"
                aria-label="Toggle sidebar"
                aria-controls="appSidebar"
                aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <h1 class="wa-page-title">{{ $pageTitle }}</h1>
    </div>

    <div class="wa-topbar-right">
        <div class="wa-profile">
            <button type="button"
                    id="profileToggle"
                    class="wa-profile-button"
                    aria-label="User account menu"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="profileMenu">
                <span class="wa-avatar" aria-hidden="true">
                    @if($userProfileImage)
                        <img
                            src="{{ $userProfileImage }}"
                            alt=""
                            onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                        <span hidden>{{ strtoupper(substr($userName, 0, 1)) }}</span>
                    @else
                        {{ strtoupper(substr($userName, 0, 1)) }}
                    @endif
                </span>
                <span class="wa-profile-name">
                    {{ $userName }}
                </span>
            </button>

            <div class="wa-profile-menu"
                 id="profileMenu"
                 aria-label="User account options"
                 hidden>
                <div class="wa-profile-info">
                    <strong>{{ $userName }}</strong>
                    @if($userEmail !== '')
                        <span class="wa-profile-email">{{ $userEmail }}</span>
                    @endif
                    <small>{{ $userRole }}</small>
                </div>

            </div>
        </div>
    </div>

</header>
