<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\PurchaseReportController;
use App\Http\Controllers\Reports\StockReportController;
use App\Http\Controllers\Reports\CustomerOutstandingReportController;

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/

// Send visitors to the login page.
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

// Show the login page and handle login requests.
Route::middleware('guest')->group(function () {

    Route::get('/login', [
        LoginController::class,
        'showLoginForm'
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login'
    ])->name('login.submit');

});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

// Require sign-in for protected pages.
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [
        LoginController::class,
        'logout'
    ])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Active User & Active Role Protected Routes
    |--------------------------------------------------------------------------
    */

    // Check access before opening application pages.
    Route::middleware('permission')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    // Register customer management routes.
    Route::resource('customers', CustomerController::class)
        ->except(['show'])
        ->middlewareFor(
            ['index'],
            'permission:customers.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:customers.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:customers.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:customers.delete'
        );

    // Show a customer's statement.
    Route::get('customers/{customer}/statement', [
        CustomerStatementController::class,
        'show'
    ])
        ->middleware([
            'permission:customers.view',
            'permission:invoices.view',
            'permission:payments.view'
        ])
        ->name('customers.statement');

    // Print a customer's statement.
    Route::get('customers/{customer}/statement/print', [
        CustomerStatementController::class,
        'print'
    ])
        ->middleware([
            'permission:customers.view',
            'permission:invoices.view',
            'permission:payments.view'
        ])
        ->name('customers.statement.print');

    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */

    // Register supplier management routes.
    Route::resource('suppliers', SupplierController::class)
        ->except(['show'])
        ->middlewareFor(
            ['index'],
            'permission:suppliers.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:suppliers.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:suppliers.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:suppliers.delete'
        );

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    // Register category management routes.
    Route::resource('categories', CategoryController::class)
        ->except(['show'])
        ->middlewareFor(
            ['index'],
            'permission:categories.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:categories.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:categories.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:categories.delete'
        );

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    // Register product management routes.
    Route::resource('products', ProductController::class)
        ->except(['show'])
        ->middlewareFor(
            ['index'],
            'permission:products.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:products.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:products.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:products.delete'
        );

    /*
    |--------------------------------------------------------------------------
    | Purchases
    |--------------------------------------------------------------------------
    */

    // Register purchase management routes.
    Route::resource('purchases', PurchaseController::class)
        ->only([
            'index',
            'create',
            'store',
            'show'
        ])
        ->middlewareFor(
            ['index', 'show'],
            'permission:purchases.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:purchases.create'
        );

    /*
    |--------------------------------------------------------------------------
    | Invoices
    |--------------------------------------------------------------------------
    */

    // Register invoice management routes.
    Route::resource('invoices', InvoiceController::class)
        ->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update'
        ])
        ->middlewareFor(
            ['index', 'show'],
            'permission:invoices.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:invoices.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:invoices.edit'
        );

    // Cancel an invoice.
    Route::post('invoices/{invoice}/cancel', [
        InvoiceController::class,
        'cancel'
    ])
        ->middleware('permission:invoices.edit')
        ->name('invoices.cancel');

    // Print an invoice.
    Route::get('invoices/{invoice}/print', [
        InvoiceController::class,
        'print'
    ])
        ->middleware('permission:invoices.view')
        ->name('invoices.print');

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    // Register payment management routes.
    Route::resource('payments', PaymentController::class)
        ->only([
            'index',
            'create',
            'store',
            'show'
        ])
        ->middlewareFor(
            ['index', 'show'],
            'permission:payments.view'
        )
        ->middlewareFor(
            ['create', 'store'],
            'permission:payments.create'
        );

    // Reverse a payment.
    Route::post('payments/{payment}/reverse', [
        PaymentController::class,
        'reverse'
    ])
        ->middleware('permission:payments.edit')
        ->name('payments.reverse');

    // Show a payment receipt.
    Route::get('payments/{payment}/receipt', [
        PaymentController::class,
        'receipt'
    ])
        ->middleware('permission:payments.view')
        ->name('payments.receipt');

        /*
        |--------------------------------------------------------------------------
        | Sales Report
        |--------------------------------------------------------------------------
        */

        // Display the sales report.
        Route::get('/reports/sales', [
            SalesReportController::class,
            'index'
        ])
            ->middleware(['permission:reports.view', 'permission:invoices.view'])
            ->name('reports.sales');

        // Export the sales report.
        Route::get('/reports/sales/export', [
            SalesReportController::class,
            'exportCsv'
        ])
            ->middleware(['permission:reports.view', 'permission:invoices.view'])
            ->name('reports.sales.export');

        /*
        |--------------------------------------------------------------------------
        | Purchase Report
        |--------------------------------------------------------------------------
        */

        // Display the purchase report.
        Route::get('/reports/purchases', [
            PurchaseReportController::class,
            'index'
        ])
            ->middleware(['permission:reports.view', 'permission:purchases.view'])
            ->name('reports.purchases');

        // Export the purchase report.
        Route::get('/reports/purchases/export', [
            PurchaseReportController::class,
            'exportCsv'
        ])
            ->middleware(['permission:reports.view', 'permission:purchases.view'])
            ->name('reports.purchases.export');

        /*
        |--------------------------------------------------------------------------
        | Stock Report
        |--------------------------------------------------------------------------
        */

        // Display the stock report.
        Route::get('/reports/stock', [
            StockReportController::class,
            'index'
        ])
            ->middleware(['permission:reports.view', 'permission:purchases.view'])
            ->name('reports.stock');

        // Export the stock report.
        Route::get('/reports/stock/export', [
            StockReportController::class,
            'exportCsv'
        ])
            ->middleware(['permission:reports.view', 'permission:purchases.view'])
            ->name('reports.stock.export');

        /*
        |--------------------------------------------------------------------------
        | Customer Outstanding Report
        |--------------------------------------------------------------------------
        */

        // Display the customer outstanding report.
        Route::get('/reports/customer-outstanding', [
            CustomerOutstandingReportController::class,
            'index'
        ])
            ->middleware([
                'permission:reports.view',
                'permission:customers.view',
                'permission:invoices.view',
                'permission:payments.view'
            ])
            ->name('reports.customer-outstanding');

        // Export the customer outstanding report.
        Route::get('/reports/customer-outstanding/export', [
            CustomerOutstandingReportController::class,
            'exportCsv'
        ])
            ->middleware([
                'permission:reports.view',
                'permission:customers.view',
                'permission:invoices.view',
                'permission:payments.view'
            ])
            ->name('reports.customer-outstanding.export');

    /*
    |--------------------------------------------------------------------------
    | Users Management
    |--------------------------------------------------------------------------
    */

    // Register user management routes.
    Route::resource('users', UserController::class)
        ->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update',
            'destroy'
        ])
        ->middleware('permission:users_roles.view')
        ->middlewareFor(
            ['create', 'store'],
            'permission:users_roles.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:users_roles.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:users_roles.delete'
        );

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions Management
    |--------------------------------------------------------------------------
    */

    // Update the permissions assigned to a role.
    Route::post('/roles/{role}/permissions', [
        RoleController::class,
        'updatePermissions'
    ])
        ->middleware('permission:users_roles.edit')
        ->name('roles.permissions.update');

    // Register role management routes.
    Route::resource('roles', RoleController::class)
        ->except(['show'])
        ->middleware('permission:users_roles.view')
        ->middlewareFor(
            ['create', 'store'],
            'permission:users_roles.create'
        )
        ->middlewareFor(
            ['edit', 'update'],
            'permission:users_roles.edit'
        )
        ->middlewareFor(
            ['destroy'],
            'permission:users_roles.delete'
        );

    });

});
