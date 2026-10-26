<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockReportController extends Controller
{
    // Check access to the stock report data.
    private function authorizeReportAccess(Request $request): void
    {
        $user = $request->user();

        abort_if(!$user || !$user->is_active, 403, 'Unauthorized access.');

        if ($user->hasRole('Admin')) {
            return;
        }

        abort_unless(
            $user->hasPermission('reports.view') && $user->hasPermission('purchases.view'),
            403,
            'You do not have permission to access the Stock Report.'
        );
    }

    // Validate and collect the stock report filters.
    private function filters(Request $request): array
    {
        return $request->validate([
            'category_id' => [
                'nullable', 'integer', 'exists:categories,id',
            ],
            'supplier_id' => [
                'nullable', 'integer', 'exists:suppliers,id',
            ],
            'product_id' => [
                'nullable', 'integer', 'exists:products,id',
            ],
            'stock_status' => [
                'nullable',
                Rule::in(['in_stock', 'low_stock', 'out_of_stock']),
            ],
        ]);
    }

    // Determine the report label for a product's stock level.
    private function status(int $quantity, int $minimum): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }

        if ($quantity <= $minimum) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Build the SQL query with all filters applied at the database level.
     *
     * Valuation Scope Policy:
     * Includes all catalog products matching the selected filters (both active
     * and inactive catalog items that hold physical stock), with Valuation =
     * products.quantity * products.cost_price.
     */
    // Build the filtered stock report query.
    private function baseQuery(array $filters)
    {
        $query = Product::query();

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('products.supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('products.id', $filters['product_id']);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'out_of_stock') {
                $query->where('products.quantity', '<=', 0);
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where('products.quantity', '>', 0)
                    ->whereColumn('products.quantity', '<=', 'products.reorder_level');
            } elseif ($filters['stock_status'] === 'in_stock') {
                $query->whereColumn('products.quantity', '>', 'products.reorder_level');
            }
        }

        return $query;
    }

    // Format product data for the stock report output.
    private function formatProductRow(Product $product): array
    {
        $quantity = (int) ($product->quantity ?? 0);
        $reorderLevel = (int) ($product->reorder_level ?? 0);
        $costCents = (int) round((float) ($product->cost_price ?? 0) * 100);
        $sellingCents = (int) round((float) ($product->selling_price ?? 0) * 100);
        $valuationCents = $quantity * $costCents;

        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'code' => $product->sku ?? '—',
            'sku' => $product->sku ?? '—',
            'category' => $product->category?->name ?? 'Uncategorized',
            'category_id' => $product->category_id,
            'supplier' => $product->supplier?->name ?? '—',
            'supplier_id' => $product->supplier_id,
            'image' => $product->image_path,
            'quantity' => $quantity,
            'minimum' => $reorderLevel,
            'reorder_level' => $reorderLevel,
            'cost_price' => $costCents / 100,
            'selling_price' => $sellingCents / 100,
            'valuation_cents' => $valuationCents,
            'valuation' => $valuationCents / 100,
            'is_active' => (bool) $product->is_active,
            'status' => $this->status($quantity, $reorderLevel),
        ];
    }

    // Display stock report totals and results.
    public function index(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->filters($request);
        $query = $this->baseQuery($filters);

        // Database-level aggregate summary across full filtered dataset
        $stats = (clone $query)
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('COALESCE(SUM(products.quantity), 0) as total_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN products.quantity > 0 AND products.quantity <= products.reorder_level THEN 1 ELSE 0 END), 0) as low_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN products.quantity <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(products.quantity * ROUND(products.cost_price * 100)), 0) as total_valuation_cents')
            ->first();

        $totalProducts = (int) ($stats->total_products ?? 0);
        $totalStock = (int) ($stats->total_stock ?? 0);
        $lowStock = (int) ($stats->low_stock ?? 0);
        $outOfStock = (int) ($stats->out_of_stock ?? 0);
        $totalValuationCents = (int) round((float) ($stats->total_valuation_cents ?? 0));
        $totalValuation = $totalValuationCents / 100;

        // Database-level grouped chart data
        $categoryGroups = (clone $query)
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(products.id) as product_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN products.quantity > products.reorder_level THEN 1 ELSE 0 END), 0) as in_stock_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN products.quantity > 0 AND products.quantity <= products.reorder_level THEN 1 ELSE 0 END), 0) as low_stock_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN products.quantity <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock_count')
            ->groupByRaw("COALESCE(categories.name, 'Uncategorized')")
            ->orderBy('category_name')
            ->get();

        $categoryChart = $categoryGroups->map(fn ($row) => [
            'name' => $row->category_name,
            'count' => (int) $row->product_count,
        ])->values();

        $statusChart = $categoryGroups->map(fn ($row) => [
            'name' => $row->category_name,
            'in_stock' => (int) $row->in_stock_count,
            'low_stock' => (int) $row->low_stock_count,
            'out_of_stock' => (int) $row->out_of_stock_count,
        ])->values();

        $isPrintMode = $request->boolean('print');
        $perPage = $isPrintMode ? max(1, $totalProducts) : 10;

        // Database-level pagination with eager-loaded category and supplier
        $paginated = (clone $query)
            ->with(['category:id,name', 'supplier:id,name'])
            ->orderBy('products.name')
            ->orderBy('products.id')
            ->paginate($perPage)
            ->withQueryString();

        $paginated->getCollection()->transform(
            fn (Product $product) => $this->formatProductRow($product)
        );

        return view('reports.stock', [
            'stockRows' => $paginated,
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'totalValuation' => $totalValuation,
            'categoryChart' => $categoryChart,
            'statusChart' => $statusChart,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'categoryId' => $filters['category_id'] ?? null,
            'supplierId' => $filters['supplier_id'] ?? null,
            'productId' => $filters['product_id'] ?? null,
            'stockStatus' => $filters['stock_status'] ?? null,
            'isPrintMode' => $isPrintMode,
        ]);
    }

    // Export the filtered stock report as CSV.
    public function exportCsv(Request $request)
    {
        $this->authorizeReportAccess($request);

        $filters = $this->filters($request);

        $query = $this->baseQuery($filters)
            ->with(['category:id,name', 'supplier:id,name'])
            ->orderBy('products.name')
            ->orderBy('products.id');

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'SKU',
                'Product Name',
                'Category',
                'Supplier',
                'Quantity',
                'Reorder Level',
                'Cost Price (Rs.)',
                'Selling Price (Rs.)',
                'Inventory Valuation (Rs.)',
                'Stock Status',
                'Catalog Status',
            ]);

            foreach ($query->cursor() as $product) {
                $row = $this->formatProductRow($product);

                fputcsv($output, [
                    $this->sanitizeCsvCell($row['sku']),
                    $this->sanitizeCsvCell($row['name']),
                    $this->sanitizeCsvCell($row['category']),
                    $this->sanitizeCsvCell($row['supplier']),
                    $row['quantity'],
                    $row['reorder_level'],
                    number_format($row['cost_price'], 2, '.', ''),
                    number_format($row['selling_price'], 2, '.', ''),
                    number_format($row['valuation'], 2, '.', ''),
                    ucwords(str_replace('_', ' ', $row['status'])),
                    $row['is_active'] ? 'Active' : 'Inactive',
                ]);
            }

            fclose($output);
        }, 'stock-report-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // Escape a value before writing it to a CSV cell.
    private function sanitizeCsvCell(?string $value): string
    {
        $text = (string) ($value ?? '');

        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $text;
        }

        return $text;
    }
}
