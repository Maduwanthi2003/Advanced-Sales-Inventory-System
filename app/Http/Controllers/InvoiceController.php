<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    private const MAX_DECIMAL_CENTS = 999999999999999;

    // List invoices with customer, date, payment, and search filters.
    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with('customer')
            ->withCount(['items', 'payments']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'cancelled') {
                $query->where('status', 'cancelled');
            } else {
                $query->where('status', 'active')
                    ->where('payment_status', $request->payment_status);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $baseQuery = clone $query;

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customer) use ($search) {
                      $customer->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->filled('search')) {
            $invoices->getCollection()->each(function ($invoice) use ($baseQuery) {
                $invoice->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '>', $invoice->id)->count() + 1
                );
            });
        }

        $customers = Customer::orderBy('name')->get();

        return view('invoices.index', compact('invoices', 'customers'));
    }

    // Show the invoice creation form with available customers and products.
    public function create()
    {
        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('invoices.create', compact('customers', 'products'));
    }

    // Validate invoice details and create the invoice and its items.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('is_active', true),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'tax' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $customer = Customer::whereKey($validated['customer_id'])
                ->lockForUpdate()
                ->first();

            if (!$customer || !$customer->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is inactive or unavailable.',
                ]);
            }

            $items = collect($validated['items'])->values();

            $productIds = $items->pluck('product_id')
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $lines = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'An unavailable product was selected.',
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($product->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. Available: {$product->quantity}.",
                    ]);
                }

                // Use the current database selling price, not browser input.
                $priceCents = (int) round(
                    (float) $product->selling_price * 100
                );

                if (
                    $priceCents > 0
                    && ($quantity > intdiv(self::MAX_DECIMAL_CENTS, $priceCents))
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Line item total exceeds the maximum supported amount.',
                    ]);
                }

                $lineCents = $priceCents * $quantity;

                if (
                    $lineCents > self::MAX_DECIMAL_CENTS
                    || $subtotalCents + $lineCents > self::MAX_DECIMAL_CENTS
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice subtotal exceeds the maximum supported amount.',
                    ]);
                }

                $subtotalCents += $lineCents;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price_cents' => $priceCents,
                    'line_cents' => $lineCents,
                ];
            }

            $discountCents = (int) round(
                (float) ($validated['discount'] ?? 0) * 100
            );

            $taxCents = (int) round(
                (float) ($validated['tax'] ?? 0) * 100
            );

            if ($discountCents > $subtotalCents) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the invoice subtotal.',
                ]);
            }

            $grandTotalCents = $subtotalCents - $discountCents + $taxCents;

            if ($grandTotalCents > self::MAX_DECIMAL_CENTS) {
                throw ValidationException::withMessages([
                    'tax' => 'Invoice grand total exceeds the maximum supported amount.',
                ]);
            }

            $invoice = Invoice::create([
                'invoice_number' => 'TMP-' . bin2hex(random_bytes(12)),
                'customer_id' => $validated['customer_id'],
                'user_id' => $request->user()->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                'discount' => number_format($discountCents / 100, 2, '.', ''),
                'tax' => number_format($taxCents / 100, 2, '.', ''),
                'grand_total' => number_format($grandTotalCents / 100, 2, '.', ''),
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->update([
                'invoice_number' => sprintf('INV-%04d-%03d',
                    (int) date('Y', strtotime($validated['invoice_date'])),
                    $invoice->id
                ),
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $quantity = $line['quantity'];
                $before = (int) $product->quantity;
                $after = $before - $quantity;

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => number_format(
                        $line['price_cents'] / 100, 2, '.', ''
                    ),
                    'total' => number_format(
                        $line['line_cents'] / 100, 2, '.', ''
                    ),
                ]);

                $product->update(['quantity' => $after]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'sale',
                    'quantity_change' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'notes' => 'Stock issued for ' . $invoice->invoice_number,
                ]);
            }
        }, 3);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Sales invoice created successfully.');
    }

    // Show an invoice and its related records.
    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'user', 'items.product', 'payments']);

        return view('invoices.show', compact('invoice'));
    }
    
    // Show the invoice edit form with its current items.
    public function edit(Invoice $invoice)
    {
        $invoice->load(['items.product', 'payments']);

        abort_if(
            $invoice->status === 'cancelled',
            403,
            'Cancelled invoices cannot be edited.'
        );

        abort_if(
            $invoice->payments->isNotEmpty()
                || $invoice->payment_status !== 'unpaid',
            403,
            'Invoices with payments cannot be edited.'
        );

        $customers = Customer::where(function ($query) use ($invoice) {
            $query->where('is_active', true)
                ->orWhere('id', $invoice->customer_id);
        })->orderBy('name')->get();

        $productIds = $invoice->items->pluck('product_id');

        $products = Product::where(function ($query) use ($productIds) {
            $query->where('is_active', true)
                ->orWhereIn('id', $productIds);
        })->orderBy('name')->get();

        return view('invoices.edit', compact(
            'invoice',
            'customers',
            'products'
        ));
    }

    // Validate and update an invoice and its items.
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($query) use ($invoice) {
                    $query->where('is_active', true)
                        ->orWhere('id', $invoice->customer_id);
                }),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => [
                'nullable', 'date', 'after_or_equal:invoice_date'
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'tax' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required', 'integer', 'distinct', 'exists:products,id'
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        DB::transaction(function () use ($invoice, $validated, $request) {

            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice' => 'Cancelled invoices cannot be updated.',
                ]);
            }

            if (
                $lockedInvoice->payment_status !== 'unpaid'
                || $lockedInvoice->payments()->exists()
            ) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoices with payments cannot be edited.',
                ]);
            }

            $customer = Customer::whereKey($validated['customer_id'])
                ->lockForUpdate()
                ->first();

            if (
                !$customer
                || (!$customer->is_active && (int) $customer->id !== (int) $lockedInvoice->customer_id)
            ) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is inactive or unavailable.',
                ]);
            }

            $oldItems = $lockedInvoice->items()
                ->get()
                ->keyBy('product_id');

            $newItems = collect($validated['items'])
                ->keyBy('product_id');

            $productIds = $oldItems->keys()
                ->merge($newItems->keys())
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $newLines = [];

            foreach ($newItems as $productId => $item) {
                $product = $products->get($productId);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid product selected.',
                    ]);
                }

                if (
                    !$product->is_active
                    && !$oldItems->has($productId)
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'An inactive product was selected.',
                    ]);
                }

                $quantity = (int) $item['quantity'];

                // Preserve the original selling price for existing lines.
                $priceCents = $oldItems->has($productId)
                    ? (int) round(
                        (float) $oldItems[$productId]->unit_price * 100
                    )
                    : (int) round(
                        (float) $product->selling_price * 100
                    );

                if (
                    $priceCents > 0
                    && ($quantity > intdiv(self::MAX_DECIMAL_CENTS, $priceCents))
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Line item total exceeds the maximum supported amount.',
                    ]);
                }

                $lineCents = $priceCents * $quantity;

                if (
                    $lineCents > self::MAX_DECIMAL_CENTS
                    || $subtotalCents + $lineCents > self::MAX_DECIMAL_CENTS
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice subtotal exceeds the maximum supported amount.',
                    ]);
                }

                $subtotalCents += $lineCents;

                $newLines[$productId] = [
                    'quantity' => $quantity,
                    'price_cents' => $priceCents,
                    'total_cents' => $lineCents,
                ];
            }

            $discountCents = (int) round(
                (float) ($validated['discount'] ?? 0) * 100
            );

            $taxCents = (int) round(
                (float) ($validated['tax'] ?? 0) * 100
            );

            if ($discountCents > $subtotalCents) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the invoice subtotal.',
                ]);
            }

            $grandTotalCents = $subtotalCents - $discountCents + $taxCents;

            if ($grandTotalCents > self::MAX_DECIMAL_CENTS) {
                throw ValidationException::withMessages([
                    'tax' => 'Invoice grand total exceeds the maximum supported amount.',
                ]);
            }

            foreach ($productIds as $productId) {
                $product = $products->get($productId);

                $oldQuantity = $oldItems->has($productId)
                    ? (int) $oldItems[$productId]->quantity
                    : 0;

                $newQuantity = isset($newLines[$productId])
                    ? $newLines[$productId]['quantity']
                    : 0;

                // Positive change = stock returned.
                // Negative change = additional stock sold.
                $change = $oldQuantity - $newQuantity;

                if ($change === 0) {
                    continue;
                }

                $before = (int) $product->quantity;
                $after = $before + $change;

                if ($after < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}.",
                    ]);
                }

                $product->update(['quantity' => $after]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity_change' => $change,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => Invoice::class,
                    'reference_id' => $lockedInvoice->id,
                    'notes' => 'Invoice updated: '
                        . $lockedInvoice->invoice_number,
                ]);
            }

            // Only replace item records after stock validation succeeds.
            $lockedInvoice->items()->delete();

            foreach ($newLines as $productId => $line) {
                $lockedInvoice->items()->create([
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'unit_price' => number_format(
                        $line['price_cents'] / 100, 2, '.', ''
                    ),
                    'total' => number_format(
                        $line['total_cents'] / 100, 2, '.', ''
                    ),
                ]);
            }

            $lockedInvoice->update([
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => number_format(
                    $subtotalCents / 100, 2, '.', ''
                ),
                'discount' => number_format(
                    $discountCents / 100, 2, '.', ''
                ),
                'tax' => number_format(
                    $taxCents / 100, 2, '.', ''
                ),
                'grand_total' => number_format(
                    $grandTotalCents / 100, 2, '.', ''
                ),
            ]);
        }, 3);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    // Cancel an invoice and apply the related stock adjustments.
    public function cancel(Request $request, Invoice $invoice)
    {
        DB::transaction(function () use ($invoice, $request) {

            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice' => 'This invoice is already cancelled.',
                ]);
            }

            if (
                $lockedInvoice->payment_status !== 'unpaid'
                || $lockedInvoice->payments()->exists()
            ) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoices with payments cannot be cancelled.',
                ]);
            }

            $items = $lockedInvoice->items()
                ->orderBy('product_id')
                ->get();

            $products = Product::whereIn(
                'id',
                $items->pluck('product_id')
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'invoice' => 'An invoice product was not found.',
                    ]);
                }

                $before = (int) $product->quantity;
                $after = $before + (int) $item->quantity;

                $product->update(['quantity' => $after]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity_change' => (int) $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => Invoice::class,
                    'reference_id' => $lockedInvoice->id,
                    'notes' => 'Invoice cancelled: '
                        . $lockedInvoice->invoice_number,
                ]);
            }

            $lockedInvoice->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice cancelled and stock restored.');
    }

    // Render a printable invoice.
    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'user', 'items.product', 'payments']);

        return view('invoices.print', compact('invoice'));
    }
}
