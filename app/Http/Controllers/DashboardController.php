<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Gather and display the dashboard summaries.
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('Admin');

        $canSeeCustomers = $isAdmin || $user->hasPermission('customers.view');
        $canSeeSuppliers = $isAdmin || $user->hasPermission('suppliers.view');
        $canSeeProducts = $isAdmin || $user->hasPermission('products.view');
        $canSeeSales = $isAdmin || $user->hasPermission('invoices.view');
        $canSeePayments = $isAdmin || $user->hasPermission('payments.view');
        $canSeePurchases = $isAdmin || $user->hasPermission('purchases.view');
        $canSeeInventoryStock = $isAdmin
            || $user->hasPermission('purchases.view')
            || $user->hasPermission('products.edit');

        $canCreateInvoice = $isAdmin || $user->hasPermission('invoices.create');
        $canEditProduct = $isAdmin || $user->hasPermission('products.edit');

        $timezone = config('app.timezone', 'UTC');
        $todayDate = Carbon::now($timezone)->toDateString();
        $currentYear = (int) Carbon::now($timezone)->year;

        // 1. Customers KPI (Admin & Sales User)
        $totalCustomers = null;
        if ($canSeeCustomers) {
            $totalCustomers = Customer::count();
        }

        // 2. Suppliers KPI (Admin & Inventory User)
        $totalSuppliers = null;
        if ($canSeeSuppliers) {
            $totalSuppliers = Supplier::count();
        }

        // 3. Products & Stock KPIs (Single aggregate query)
        $totalProducts = null;
        $lowStock = null;
        $stockPercent = null;
        $lowStockProducts = collect();

        if ($canSeeProducts || $canSeeInventoryStock) {
            $productStats = Product::query()
                ->selectRaw('COUNT(*) as total_products')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND quantity > reorder_level THEN 1 ELSE 0 END), 0) as active_in_stock')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND quantity > 0 AND quantity <= reorder_level THEN 1 ELSE 0 END), 0) as active_low_stock')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND quantity = 0 THEN 1 ELSE 0 END), 0) as active_out_of_stock')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) as inactive_count')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND quantity <= reorder_level THEN 1 ELSE 0 END), 0) as low_stock_total')
                ->first();

            $totalProducts = (int) ($productStats->total_products ?? 0);

            if ($canSeeInventoryStock) {
                $activeInStock = (int) ($productStats->active_in_stock ?? 0);
                $activeLowStock = (int) ($productStats->active_low_stock ?? 0);
                $activeOutOfStock = (int) ($productStats->active_out_of_stock ?? 0);
                $inactiveCount = (int) ($productStats->inactive_count ?? 0);

                // Low Stock Products KPI counts active products at or below reorder_level
                $lowStock = (int) ($productStats->low_stock_total ?? 0);

                $stockBase = max($totalProducts, 1);

                $stockPercent = [
                    'inStock' => round(($activeInStock / $stockBase) * 100, 2),
                    'lowStock' => round(($activeLowStock / $stockBase) * 100, 2),
                    'outOfStock' => round(($activeOutOfStock / $stockBase) * 100, 2),
                    'inactive' => round(($inactiveCount / $stockBase) * 100, 2),
                ];

                $lowStockProducts = Product::query()
                    ->with('category:id,name')
                    ->where('is_active', true)
                    ->whereColumn('quantity', '<=', 'reorder_level')
                    ->orderBy('quantity')
                    ->orderBy('name')
                    ->limit(5)
                    ->get();
            }
        }

        // 4. Sales KPIs, Monthly Sales Chart & Recent Invoices (Admin & Sales User)
        $todaySales = null;
        $totalSales = null;
        $salesChart = null;
        $recentInvoices = collect();

        if ($canSeeSales) {
            $salesTotals = Invoice::query()
                ->where('status', 'active')
                ->selectRaw('COALESCE(SUM(grand_total), 0) as total_sales')
                ->selectRaw('COALESCE(SUM(CASE WHEN DATE(invoice_date) = ? THEN grand_total ELSE 0 END), 0) as today_sales', [$todayDate])
                ->first();

            $totalSalesCents = (int) round((float) ($salesTotals->total_sales ?? 0) * 100);
            $todaySalesCents = (int) round((float) ($salesTotals->today_sales ?? 0) * 100);

            $totalSales = $totalSalesCents / 100;
            $todaySales = $todaySalesCents / 100;

            $monthlySales = Invoice::query()
                ->where('status', 'active')
                ->whereYear('invoice_date', $currentYear)
                ->selectRaw('MONTH(invoice_date) AS month, SUM(grand_total) AS total')
                ->groupByRaw('MONTH(invoice_date)')
                ->pluck('total', 'month');

            $salesChart = collect(range(1, 12))
                ->map(fn ($month) => round((float) ($monthlySales[$month] ?? 0), 2))
                ->values()
                ->all();

            $recentInvoices = Invoice::query()
                ->with('customer:id,name')
                ->where('status', 'active')
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        // 5. Outstanding Payments KPI (Admin & Sales User)
        $outstandingPayments = null;

        if ($canSeePayments) {
            $unpaidOrPartialInvoices = Invoice::query()
                ->where('status', 'active')
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->withSum([
                    'payments as completed_paid_amount' => function ($query) {
                        $query->where('status', 'completed');
                    },
                ], 'amount')
                ->get(['id', 'grand_total', 'payment_status']);

            $outstandingCents = 0;

            foreach ($unpaidOrPartialInvoices as $inv) {
                $invTotalCents = (int) round((float) $inv->grand_total * 100);
                $invPaidCents = (int) round((float) ($inv->completed_paid_amount ?? 0) * 100);
                $outstandingCents += max(0, $invTotalCents - $invPaidCents);
            }

            $outstandingPayments = $outstandingCents / 100;
        }

        // 6. Total Purchases KPI (Admin & Inventory User)
        $totalPurchases = null;

        if ($canSeePurchases) {
            $purchasesSum = Purchase::query()
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $totalPurchases = ((int) round((float) $purchasesSum * 100)) / 100;
        }

        return view('dashboard.index', [
            'canSeeCustomers' => $canSeeCustomers,
            'canSeeSuppliers' => $canSeeSuppliers,
            'canSeeProducts' => $canSeeProducts,
            'canSeeSales' => $canSeeSales,
            'canSeePayments' => $canSeePayments,
            'canSeePurchases' => $canSeePurchases,
            'canSeeInventoryStock' => $canSeeInventoryStock,
            'canCreateInvoice' => $canCreateInvoice,
            'canEditProduct' => $canEditProduct,
            'currentYear' => $currentYear,
            'totalCustomers' => $totalCustomers,
            'totalSuppliers' => $totalSuppliers,
            'totalProducts' => $totalProducts,
            'todaySales' => $todaySales,
            'totalSales' => $totalSales,
            'outstandingPayments' => $outstandingPayments,
            'totalPurchases' => $totalPurchases,
            'lowStock' => $lowStock,
            'stockPercent' => $stockPercent,
            'salesChart' => $salesChart,
            'recentInvoices' => $recentInvoices,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
