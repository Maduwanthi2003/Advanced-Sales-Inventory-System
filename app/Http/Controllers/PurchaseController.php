<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    // List purchases with search and request filters.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Purchase::with('supplier')
            ->when($request->filled('from_date'), fn ($q) =>
                $q->whereDate('purchase_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) =>
                $q->whereDate('purchase_date', '<=', $request->to_date))
            ->when($request->filled('supplier_id'), fn ($q) =>
                $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status));

        $purchases = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('purchase_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($supplier) use ($search) {
                          $supplier->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($search !== '') {
            $purchases->getCollection()->each(function ($purchase) use ($baseQuery) {
                $purchase->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '>', $purchase->id)->count() + 1
                );
            });
        }

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact(
            'purchases', 'suppliers', 'search'
        ));
    }

    // Show the purchase form with active suppliers and products.
    public function create()
    {
        return view('purchases.create', [
            'suppliers' => Supplier::where('is_active', true)
                ->orderBy('name')->get(),
            'products' => Product::where('is_active', true)
                ->orderBy('name')->get(),
            'purchaseNumber' => 'Assigned when saved',
        ]);
    }

    // Validate and record a purchase with its items.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')
                    ->where('is_active', 1),
            ],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('is_active', 1),
            ],
            'items.*.unit_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
            'items.*.quantity' => [
                'required', 'integer', 'min:1', 'max:4294967295'
            ],
        ]);

        $purchase = DB::transaction(function () use ($validated, $request) {
            
            // Lock all products in a consistent order.
            $ids = collect($validated['items'])
                ->pluck('product_id')
                ->sort()
                ->values();

            $products = Product::whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $purchase = Purchase::create([
                'purchase_number' => 'TMP-' . bin2hex(random_bytes(12)),
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $request->user()->id,
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => 0,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            $purchase->update([
                'purchase_number' => sprintf('PUR-%05d', $purchase->id),
            ]);

            $totalCents = 0;

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => 'A selected product is unavailable.',
                    ]);
                }

                $before = (int) $product->quantity;
                $quantity = (int) $item['quantity'];

                if ($quantity > 4294967295 - $before) {
                    throw ValidationException::withMessages([
                        'items' => "Stock limit exceeded for {$product->name}.",
                    ]);
                }

                $unitCents = $this->moneyToCents($item['unit_cost']);
                $lineCents = $unitCents * $quantity;

                if ($lineCents > 999999999999999 ||
                    $totalCents + $lineCents > 999999999999999) {
                    throw ValidationException::withMessages([
                        'items' => 'Purchase total exceeds the supported amount.',
                    ]);
                }

                $totalCents += $lineCents;
                $after = $before + $quantity;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $this->centsToMoney($unitCents),
                    'total' => $this->centsToMoney($lineCents),
                ]);

                $product->update(['quantity' => $after]);

                DB::table('stock_movements')->insert([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'purchase',
                    'quantity_change' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'notes' => 'Stock received through ' .
                        $purchase->purchase_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $purchase->update([
                'total_amount' => $this->centsToMoney($totalCents),
            ]);

            return $purchase;
        }, 3);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase saved and stock updated successfully.');
    }

    // Show a purchase with its supplier and line items.
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }

    // Convert a monetary value to integer cents.
    private function moneyToCents($value): int
    {
        $normalized = trim((string) $value);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages([
                'items' => 'Unit cost must have at most two decimal places.',
            ]);
        }

        $parts = explode('.', $normalized, 2);
        $whole = (int) $parts[0];
        $fraction = str_pad($parts[1] ?? '', 2, '0');

        return $whole * 100 + (int) $fraction;
    }

    // Format integer cents as a two-decimal monetary value.
    private function centsToMoney(int $cents): string
    {
        return intdiv($cents, 100) . '.' .
            str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
