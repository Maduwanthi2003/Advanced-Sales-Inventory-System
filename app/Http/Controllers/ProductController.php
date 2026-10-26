<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // List products with search and inventory filters.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $status = $request->query('status', '');

        $baseQuery = Product::query()
            ->with(['category', 'supplier'])
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($status === 'in_stock', function ($query) {
                $query->whereColumn('quantity', '>', 'reorder_level');
            })
            ->when($status === 'low_stock', function ($query) {
                $query->where('quantity', '>', 0)
                      ->whereColumn('quantity', '<=', 'reorder_level');
            })
            ->when($status === 'out_of_stock', function ($query) {
                $query->where('quantity', 0);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            });

        $products = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        if ($search !== '') {
            $products->getCollection()->each(function ($product) use ($baseQuery) {
                $product->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '<', $product->id)->count() + 1
                );
            });
        }

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact(
            'products',
            'categories',
            'search',
            'categoryId',
            'status'
        ));
    }

    // Show the product creation form with available categories and suppliers.
    public function create()
    {
        return view('products.create', [
            'categories' => Category::where('is_active', true)
                ->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)
                ->orderBy('name')->get(),
        ]);
    }

    // Validate product details and store the new product.
    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('products', 'public');
        }

        try {
            Product::create($data);
        } catch (\Throwable $e) {
            if (!empty($data['image_path'])) {
                Storage::disk('public')->delete($data['image_path']);
            }

            throw $e;
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    // Show the product edit form with its related data.
    public function edit(Product $product)
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orWhere('id', $product->category_id)
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orWhere('id', $product->supplier_id)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact(
            'product',
            'categories',
            'suppliers'
        ));
    }

    // Validate product changes and update the existing record.
    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);

        unset($data['image']);

        $oldImage = $product->image_path;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')
                ->store('products', 'public');

            $data['image_path'] = $newImage;
        }

        try {
            $product->update($data);
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    // Delete a product and its stored image when permitted.
    public function destroy(Product $product)
    {
        if (
            $product->purchaseItems()->exists() ||
            $product->invoiceItems()->exists() ||
            $product->stockMovements()->exists()
        ) {
            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'This product has transaction history and cannot be deleted.'
                );
        }

        $imagePath = $product->image_path;

        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Validate product fields and any uploaded image.
    private function validateProduct(
        Request $request,
        ?Product $product = null
    ): array {
        $skuRule = Rule::unique('products', 'sku');

        if ($product) {
            $skuRule->ignore($product->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:100', $skuRule],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id'),
            ],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id'),
            ],
            'cost_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'selling_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', Rule::in(['0', '1'])],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ]);
    }
}
