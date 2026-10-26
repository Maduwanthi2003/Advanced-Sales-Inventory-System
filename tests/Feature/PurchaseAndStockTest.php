<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseAndStockTest extends TestCase
{
    use DatabaseTransactions;

    private function createInventoryUser(): User
    {
        $role = Role::whereRaw('LOWER(name) = ?', ['inventory user'])->firstOrFail();

        return User::create([
            'name' => 'Inventory Tester',
            'email' => uniqid('inv_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function createSupplierAndProducts(): array
    {
        $category = Category::create([
            'name' => 'Hardware ' . uniqid(),
            'description' => 'Test Category',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Apex Supplier',
            'company_name' => 'Apex Holdings Pvt Ltd',
            'contact_person' => 'Kamal Perera',
            'phone' => '0771112233',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'name' => 'Industrial Drill',
            'sku' => 'SKU-DRILL-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '1250.50',
            'selling_price' => '1800.00',
            'quantity' => 10,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Safety Helmet',
            'sku' => 'SKU-HELM-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '450.25',
            'selling_price' => '750.00',
            'quantity' => 4,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        return [$supplier, $productA, $productB];
    }

    public function test_multi_item_purchase_increases_stock_calculates_exact_total_and_logs_movements(): void
    {
        $user = $this->createInventoryUser();
        [$supplier, $productA, $productB] = $this->createSupplierAndProducts();

        // 5 * 1250.50 = 6252.50; 8 * 450.25 = 3602.00; Total = 9854.50
        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-10-15',
            'notes' => 'Restocking order',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 5,
                    'unit_cost' => '1250.50',
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 8,
                    'unit_cost' => '450.25',
                ],
            ],
        ]);

        $purchase = Purchase::latest('id')->first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('purchases.show', $purchase));

        $this->assertSame('9854.50', (string) $purchase->total_amount);
        $this->assertSame('completed', $purchase->status);
        $this->assertCount(2, $purchase->items);

        $this->assertSame(15, $productA->fresh()->quantity);
        $this->assertSame(12, $productB->fresh()->quantity);

        $movementA = StockMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->where('product_id', $productA->id)
            ->first();

        $this->assertNotNull($movementA);
        $this->assertSame('purchase', $movementA->type);
        $this->assertSame(10, $movementA->stock_before);
        $this->assertSame(5, $movementA->quantity_change);
        $this->assertSame(15, $movementA->stock_after);
    }

    public function test_invalid_purchase_rolls_back_without_partial_records_or_stock_changes(): void
    {
        $user = $this->createInventoryUser();
        [$supplier, $productA, $productB] = $this->createSupplierAndProducts();

        $initialPurchaseCount = Purchase::count();

        // Second item has more than 2 decimal places in unit_cost (triggers ValidationException inside transaction)
        $response = $this->actingAs($user)->from(route('purchases.create'))->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-10-15',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 5,
                    'unit_cost' => '1250.50',
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 3,
                    'unit_cost' => '450.259',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame($initialPurchaseCount, Purchase::count());
        $this->assertSame(10, $productA->fresh()->quantity);
        $this->assertSame(4, $productB->fresh()->quantity);
    }
}
