@php
    /*
    |--------------------------------------------------------------------------
    | Authenticated User & Role
    |--------------------------------------------------------------------------
    */

    $user = auth()->user();
    $userProfileImage = $user?->profile_image_url;
    $role = $user?->role;

    $isAdmin = $user
        && (bool) $user->is_active
        && $role
        && (bool) $role->is_active
        && strcasecmp((string) $role->name, 'Admin') === 0;

    /*
    |--------------------------------------------------------------------------
    | Permission Checker
    |--------------------------------------------------------------------------
    */

    $can = function (string $permission) use ($user, $role, $isAdmin): bool {

        if (
            !$user ||
            !(bool) $user->is_active ||
            !$role ||
            !(bool) $role->is_active
        ) {
            return false;
        }

        if ($isAdmin) {
            return true;
        }

        return $role->hasPermission($permission);
    };

    /*
    |--------------------------------------------------------------------------
    | Main Sidebar Menus
    |--------------------------------------------------------------------------
    */

    $menus = [
        [
            'Dashboard',
            'dashboard',
            'dashboard',
            'home',
            null
        ],
        [
            'Customers',
            'customers.index',
            'customers.*',
            'users',
            'customers.view'
        ],
        [
            'Suppliers',
            'suppliers.index',
            'suppliers.*',
            'truck',
            'suppliers.view'
        ],
        [
            'Categories',
            'categories.index',
            'categories.*',
            'grid',
            'categories.view'
        ],
        [
            'Products',
            'products.index',
            'products.*',
            'box',
            'products.view'
        ],
        [
            'Purchases',
            'purchases.index',
            'purchases.*',
            'cart',
            'purchases.view'
        ],
        [
            'Invoices',
            'invoices.index',
            'invoices.*',
            'file',
            'invoices.view'
        ],
        [
            'Payments',
            'payments.index',
            'payments.*',
            'card',
            'payments.view'
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Reports Dropdown
    |--------------------------------------------------------------------------
    */

    $canSeeSalesReport = $can('reports.view') && $can('invoices.view');
    $canSeeCustomerOutstandingReport = $can('reports.view')
        && $can('customers.view')
        && $can('invoices.view')
        && $can('payments.view');
    $canSeePurchaseReport = $can('reports.view') && $can('purchases.view');
    $canSeeStockReport = $can('reports.view') && $can('purchases.view');

    $canSeeReports = $canSeeSalesReport
        || $canSeeCustomerOutstandingReport
        || $canSeePurchaseReport
        || $canSeeStockReport;

    $reportsActive = request()->routeIs('reports.*');

    /*
    |--------------------------------------------------------------------------
    | Users & Roles Dropdown
    |--------------------------------------------------------------------------
    */

    $canSeeUsersRoles = (bool) $isAdmin;

    $usersRolesActive =
        request()->routeIs('users.*') ||
        request()->routeIs('roles.*');

@endphp

<aside class="wa-sidebar" id="appSidebar">

    {{-- ========================================
         BRAND
    ======================================== --}}

    <div class="wa-brand">

        <div class="wa-brand-title">

            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path d="M24 3 43 14v20L24 45 5 34V14L24 3Z"/>
                <path d="M5 14 24 25 43 14M24 25v20"/>
            </svg>

            <strong>WebAdmin</strong>

        </div>

        <p>
            Inventory &amp; Sales<br>
            Management System
        </p>

    </div>

    {{-- ========================================
         NAVIGATION
    ======================================== --}}

    <nav class="wa-nav" aria-label="Main navigation">

        {{-- NORMAL MENU ITEMS --}}

        @foreach($menus as $menu)

            @php
                [
                    $label,
                    $routeName,
                    $pattern,
                    $icon,
                    $permission
                ] = $menu;

                $allowed = $permission === null
                    ? (bool) (
                        $user?->is_active &&
                        $role?->is_active
                    )
                    : $can($permission);

                $exists = \Illuminate\Support\Facades\Route::has(
                    $routeName
                );

                $active = request()->routeIs($pattern);
            @endphp

            @continue(!$allowed)

            @if($exists)

                <a
                    href="{{ route($routeName) }}"
                    class="wa-nav-link {{ $active ? 'is-active' : '' }}"
                    @if($active)
                        aria-current="page"
                    @endif
                >

            @else

                <span
                    class="wa-nav-link is-disabled"
                    title="Page not available yet"
                >

            @endif

                <span class="wa-nav-icon">
                    <x-sidebar-icon :name="$icon" />
                </span>

                <span>{{ $label }}</span>

            @if($exists)

                </a>

            @else

                </span>

            @endif

        @endforeach

        {{-- ========================================
             REPORTS DROPDOWN
        ======================================== --}}

        @if($canSeeReports)

            <div
                class="wa-report-menu {{ $reportsActive ? 'is-open' : '' }}"
            >

                <button
                    type="button"
                    class="wa-nav-link wa-report-toggle {{ $reportsActive ? 'is-active' : '' }}"
                    aria-expanded="{{ $reportsActive ? 'true' : 'false' }}"
                    aria-controls="reportsSubmenu"
                >

                    <span class="wa-nav-icon">
                        <x-sidebar-icon name="chart" />
                    </span>

                    <span class="wa-report-title">
                        Reports
                    </span>

                    <svg
                        class="wa-report-chevron"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>

                </button>

                <div
                    class="wa-report-submenu"
                    id="reportsSubmenu"
                    @if(!$reportsActive)
                        hidden
                    @endif
                >

                    {{-- SALES REPORT --}}

                    @if(
                        $canSeeSalesReport &&
                        \Illuminate\Support\Facades\Route::has(
                            'reports.sales'
                        )
                    )

                        <a
                            href="{{ route('reports.sales') }}"
                            class="wa-report-sublink {{ request()->routeIs('reports.sales') ? 'is-active' : '' }}"
                            @if(request()->routeIs('reports.sales'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Sales Report</span>

                        </a>

                    @endif

                    {{-- CUSTOMER OUTSTANDING REPORT --}}

                    @if(
                        $canSeeCustomerOutstandingReport &&
                        \Illuminate\Support\Facades\Route::has(
                            'reports.customer-outstanding'
                        )
                    )

                        <a
                            href="{{ route('reports.customer-outstanding') }}"
                            class="wa-report-sublink {{ request()->routeIs('reports.customer-outstanding') ? 'is-active' : '' }}"
                            @if(request()->routeIs('reports.customer-outstanding'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Customer Outstanding</span>

                        </a>

                    @endif

                    {{-- PURCHASE REPORT --}}

                    @if(
                        $canSeePurchaseReport &&
                        \Illuminate\Support\Facades\Route::has(
                            'reports.purchases'
                        )
                    )

                        <a
                            href="{{ route('reports.purchases') }}"
                            class="wa-report-sublink {{ request()->routeIs('reports.purchases') ? 'is-active' : '' }}"
                            @if(request()->routeIs('reports.purchases'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Purchase Report</span>

                        </a>

                    @endif

                    {{-- STOCK REPORT --}}

                    @if(
                        $canSeeStockReport &&
                        \Illuminate\Support\Facades\Route::has(
                            'reports.stock'
                        )
                    )

                        <a
                            href="{{ route('reports.stock') }}"
                            class="wa-report-sublink {{ request()->routeIs('reports.stock') ? 'is-active' : '' }}"
                            @if(request()->routeIs('reports.stock'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Stock Report</span>

                        </a>

                    @endif

                </div>

            </div>

        @endif

        {{-- ========================================
             USERS & ROLES DROPDOWN
        ======================================== --}}

        @if($canSeeUsersRoles)

            <div
                class="wa-report-menu {{ $usersRolesActive ? 'is-open' : '' }}"
            >

                <button
                    type="button"
                    class="wa-nav-link wa-report-toggle {{ $usersRolesActive ? 'is-active' : '' }}"
                    aria-expanded="{{ $usersRolesActive ? 'true' : 'false' }}"
                    aria-controls="usersRolesSubmenu"
                >

                    <span class="wa-nav-icon">
                        <x-sidebar-icon name="team" />
                    </span>

                    <span class="wa-report-title">
                        Users &amp; Roles
                    </span>

                    <svg
                        class="wa-report-chevron"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>

                </button>

                <div
                    class="wa-report-submenu"
                    id="usersRolesSubmenu"
                    @if(!$usersRolesActive)
                        hidden
                    @endif
                >

                    {{-- USERS MANAGEMENT --}}

                    @if(
                        \Illuminate\Support\Facades\Route::has(
                            'users.index'
                        )
                    )

                        <a
                            href="{{ route('users.index') }}"
                            class="wa-report-sublink {{ request()->routeIs('users.*') ? 'is-active' : '' }}"
                            @if(request()->routeIs('users.*'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Users</span>

                        </a>

                    @endif

                    {{-- ROLES & PERMISSIONS --}}

                    @if(
                        \Illuminate\Support\Facades\Route::has(
                            'roles.index'
                        )
                    )

                        <a
                            href="{{ route('roles.index') }}"
                            class="wa-report-sublink {{ request()->routeIs('roles.*') ? 'is-active' : '' }}"
                            @if(request()->routeIs('roles.*'))
                                aria-current="page"
                            @endif
                        >

                            <span class="wa-report-dot"></span>

                            <span>Roles &amp; Permissions</span>

                        </a>

                    @endif

                </div>

            </div>

        @endif

    </nav>

    {{-- ========================================
         SIDEBAR FOOTER
    ======================================== --}}

    <div class="wa-sidebar-bottom">

        {{-- DECORATION --}}

        <div
            class="wa-sidebar-decoration"
            aria-hidden="true"
        >

            <div class="wa-cube wa-cube-a"></div>
            <div class="wa-cube wa-cube-b"></div>
            <div class="wa-cube wa-cube-c"></div>

        </div>

        {{-- USER INFORMATION --}}

        <div class="wa-sidebar-user">

            <div class="wa-sidebar-avatar">

                @if($userProfileImage)
                    <img
                        src="{{ $userProfileImage }}"
                        alt=""
                        onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                    <span hidden>
                        {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
                    </span>
                @else
                    {{
                        strtoupper(
                            substr(
                                $user?->name ?? 'A',
                                0,
                                1
                            )
                        )
                    }}
                @endif

            </div>

            <div class="wa-sidebar-user-info">

                <strong>
                    {{ $user?->name ?? 'Admin' }}
                </strong>

                <span>
                    {{ $role?->name ?? 'User' }}
                </span>

            </div>

        </div>

        {{-- LOGOUT --}}

        @if(
            \Illuminate\Support\Facades\Route::has('logout')
        )

            <form
                action="{{ route('logout') }}"
                method="POST"
                class="wa-sidebar-logout-form"
            >

                @csrf

                <button
                    type="submit"
                    class="wa-sidebar-logout"
                >

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M10 17l5-5-5-5"/>
                        <path d="M15 12H3"/>
                        <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>
                    </svg>

                    <span>Logout</span>

                </button>

            </form>

        @endif

    </div>

</aside>
