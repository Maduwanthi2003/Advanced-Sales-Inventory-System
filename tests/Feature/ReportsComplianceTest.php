<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportsComplianceTest extends TestCase
{
    use DatabaseTransactions;

    private function seedReportData(): array
    {
        $adminRole = Role::whereRaw('LOWER(name) = ?', ['admin'])->firstOrFail();
        $salesRole = Role::whereRaw('LOWER(name) = ?', ['sales user'])->firstOrFail();
        $invRole = Role::whereRaw('LOWER(name) = ?', ['inventory user'])->firstOrFail();

        $admin = User::create([
            'name' => 'Admin Report User',
            'email' => uniqid('adm_rep_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $sales = User::create([
            'name' => 'Sales Report User',
            'email' => uniqid('sal_rep_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $salesRole->id,
            'is_active' => true,
        ]);

        $inventory = User::create([
            'name' => 'Inventory Report User',
            'email' => uniqid('inv_rep_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $invRole->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Report Customer',
            'contact_person' => 'Amila',
            'phone' => '0770001122',
            'email' => 'rep_cust@example.com',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Report Category ' . uniqid(),
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Report Supplier',
            'company_name' => 'Report Supplier Co',
            'contact_person' => 'Chathura',
            'phone' => '0773334455',
            'email' => 'rep_sup@example.com',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'UPS 1200VA',
            'sku' => 'SKU-UPS-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '10000.00',
            'selling_price' => '15000.00',
            'quantity' => 5,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        return [$admin, $sales, $inventory, $customer, $supplier, $product];
    }

    public function test_sales_purchase_stock_and_outstanding_reports_prevent_double_counting_and_export_csv(): void
    {
        [$admin, $sales, $inventory, $customer, $supplier, $product] = $this->seedReportData();

        // 1. Record a purchase of 5 units @ 10,000 = 50,000 (stock becomes 10)
        $this->actingAs($inventory)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-10-10',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => '10000.00'],
            ],
        ]);

        // 2. Record an invoice of 2 units @ 15,000 = 30,000 (stock becomes 8)
        $this->actingAs($sales)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-12',
            'due_date' => '2026-10-25',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);
        $invoice = Invoice::latest('id')->firstOrFail();

        // 3. Record TWO partial payments (5,000 + 7,000 = 12,000; Outstanding = 18,000)
        // Verifies that multiple payments on one invoice do NOT double-count the 30,000 invoice total!
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-13',
            'payment_method' => 'cash',
            'amount' => '5000.00',
        ]);
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-14',
            'payment_method' => 'bank_transfer',
            'amount' => '7000.00',
        ]);

        // A. Sales Report (accessible to Sales User & Admin, 403 to Inventory User)
        $this->actingAs($sales)
            ->get(route('reports.sales', ['customer_id' => $customer->id]))
            ->assertStatus(200)
            ->assertSee('30,000.00')
            ->assertSee('12,000.00')
            ->assertSee('18,000.00');

        $this->actingAs($inventory)->get(route('reports.sales'))->assertStatus(403);

        $salesCsv = $this->actingAs($sales)->get(route('reports.sales.export', ['customer_id' => $customer->id]));
        $salesCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $salesCsv->headers->get('Content-Type'));

        // B. Customer Outstanding Report (accessible to Sales User & Admin, 403 to Inventory User)
        $this->actingAs($sales)
            ->get(route('reports.customer-outstanding', ['search' => $customer->name]))
            ->assertStatus(200)
            ->assertSee('30,000.00')
            ->assertSee('12,000.00')
            ->assertSee('18,000.00');

        $this->actingAs($inventory)->get(route('reports.customer-outstanding'))->assertStatus(403);

        $outstandingCsv = $this->actingAs($sales)->get(route('reports.customer-outstanding.export'));
        $outstandingCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $outstandingCsv->headers->get('Content-Type'));

        // C. Purchase Report (accessible to Inventory User & Admin, 403 to Sales User)
        $this->actingAs($inventory)
            ->get(route('reports.purchases', ['supplier_id' => $supplier->id]))
            ->assertStatus(200)
            ->assertSee('50,000.00');

        $this->actingAs($sales)->get(route('reports.purchases'))->assertStatus(403);

        $purchaseCsv = $this->actingAs($inventory)->get(route('reports.purchases.export'));
        $purchaseCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $purchaseCsv->headers->get('Content-Type'));

        // D. Stock Report (8 units remaining: Cost Price 10,000.00, Selling Price 15,000.00, Valuation 80,000.00)
        $this->actingAs($inventory)
            ->get(route('reports.stock', ['search' => $product->sku]))
            ->assertStatus(200)
            ->assertSee('10,000.00')
            ->assertSee('15,000.00')
            ->assertSee('80,000.00');

        $this->actingAs($sales)->get(route('reports.stock'))->assertStatus(403);

        $stockCsv = $this->actingAs($inventory)->get(route('reports.stock.export', ['search' => $product->sku]));
        $stockCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $stockCsv->headers->get('Content-Type'));
    }
}
