<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoiceAndInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private function createSalesSetup(): array
    {
        $role = Role::whereRaw('LOWER(name) = ?', ['sales user'])->firstOrFail();

        $user = User::create([
            'name' => 'Sales Tester',
            'email' => uniqid('sales_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Lanka Traders',
            'contact_person' => 'Nimal Silva',
            'phone' => '0712233445',
            'email' => 'lanka@example.com',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Electronics ' . uniqid(),
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Tech Supplier',
            'company_name' => 'Tech Corp',
            'contact_person' => 'Sunil',
            'phone' => '0779988776',
            'email' => 'tech@example.com',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'name' => 'LED Monitor',
            'sku' => 'SKU-MON-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '20000.00',
            'selling_price' => '25000.00',
            'quantity' => 10,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Mechanical Keyboard',
            'sku' => 'SKU-KEY-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '5000.00',
            'selling_price' => '7500.50',
            'quantity' => 6,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        return [$user, $customer, $productA, $productB];
    }

    public function test_invoice_creation_decreases_stock_and_calculates_monetary_discount_and_tax_accurately(): void
    {
        [$user, $customer, $productA, $productB] = $this->createSalesSetup();

        // 2 * 25000.00 = 50000.00; 2 * 7500.50 = 15001.00; Subtotal = 65001.00
        // Discount = 1001.00; Tax = 500.25 -> Grand Total = 64500.25
        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-18',
            'due_date' => '2026-10-25',
            'discount' => '1001.00',
            'tax' => '500.25',
            'notes' => 'Corporate order',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertSame('65001.00', (string) $invoice->subtotal);
        $this->assertSame('1001.00', (string) $invoice->discount);
        $this->assertSame('500.25', (string) $invoice->tax);
        $this->assertSame('64500.25', (string) $invoice->grand_total);
        $this->assertSame('unpaid', $invoice->payment_status);

        $this->assertSame(8, $productA->fresh()->quantity);
        $this->assertSame(4, $productB->fresh()->quantity);

        $saleMovement = StockMovement::where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->where('product_id', $productA->id)
            ->first();

        $this->assertNotNull($saleMovement);
        $this->assertSame('sale', $saleMovement->type);
        $this->assertSame(-2, $saleMovement->quantity_change);
        $this->assertSame(10, $saleMovement->stock_before);
        $this->assertSame(8, $saleMovement->stock_after);
    }

    public function test_overselling_and_discount_exceeding_subtotal_are_rejected_and_rolled_back(): void
    {
        [$user, $customer, $productA, $productB] = $this->createSalesSetup();

        $initialInvoiceCount = Invoice::count();

        // Request 15 units when only 10 are in stock
        $oversellResponse = $this->actingAs($user)->from(route('invoices.create'))->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-18',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 15],
            ],
        ]);

        $oversellResponse->assertSessionHasErrors('items');
        $this->assertSame($initialInvoiceCount, Invoice::count());
        $this->assertSame(10, $productA->fresh()->quantity);

        // Request discount (30000) greater than subtotal (25000)
        $discountResponse = $this->actingAs($user)->from(route('invoices.create'))->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-18',
            'discount' => '30000.00',
            'tax' => '0.00',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 1],
            ],
        ]);

        $discountResponse->assertSessionHasErrors('discount');
        $this->assertSame($initialInvoiceCount, Invoice::count());
        $this->assertSame(10, $productA->fresh()->quantity);
    }

    public function test_unpaid_invoice_editing_reconciles_stock_and_cancellation_restores_stock(): void
    {
        [$user, $customer, $productA, $productB] = $this->createSalesSetup();

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-18',
            'discount' => '0.00',
            'tax' => '0.00',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 3], // stock 10 -> 7
                ['product_id' => $productB->id, 'quantity' => 2], // stock 6 -> 4
            ],
        ]);

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertSame(7, $productA->fresh()->quantity);
        $this->assertSame(4, $productB->fresh()->quantity);

        // Edit invoice: change productA qty from 3 to 1 (+2 returned -> 9), productB qty from 2 to 4 (-2 sold -> 2)
        $updateResponse = $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-19',
            'discount' => '500.00',
            'tax' => '100.00',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 1],
                ['product_id' => $productB->id, 'quantity' => 4],
            ],
        ]);

        $updateResponse->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame(9, $productA->fresh()->quantity);
        $this->assertSame(2, $productB->fresh()->quantity);

        // Cancel invoice: restores 1 to productA (9 -> 10) and 4 to productB (2 -> 6)
        $cancelResponse = $this->actingAs($user)->post(route('invoices.cancel', $invoice));
        $cancelResponse->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertSame(10, $productA->fresh()->quantity);
        $this->assertSame(6, $productB->fresh()->quantity);
    }
}
