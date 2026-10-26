<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CustomerStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentAndStatementTest extends TestCase
{
    use DatabaseTransactions;

    private function createBillingSetup(): array
    {
        $adminRole = Role::whereRaw('LOWER(name) = ?', ['admin'])->firstOrFail();
        $salesRole = Role::whereRaw('LOWER(name) = ?', ['sales user'])->firstOrFail();

        $admin = User::create([
            'name' => 'Admin Tester',
            'email' => uniqid('admin_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $sales = User::create([
            'name' => 'Sales Tester',
            'email' => uniqid('sales_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $salesRole->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Ceylon Retail Pvt Ltd',
            'contact_person' => 'Ruwan Fernando',
            'phone' => '0775566778',
            'email' => 'ceylon@example.com',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Office ' . uniqid(),
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Office Supplier',
            'company_name' => 'Office Supply Co',
            'contact_person' => 'Kasun',
            'phone' => '0714455667',
            'email' => 'office@example.com',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Laser Printer',
            'sku' => 'SKU-PRT-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '30000.00',
            'selling_price' => '40000.00',
            'quantity' => 20,
            'reorder_level' => 4,
            'is_active' => true,
        ]);

        return [$admin, $sales, $customer, $product];
    }

    public function test_partial_and_full_payments_overpayment_prevention_and_admin_reversal(): void
    {
        [$admin, $sales, $customer, $product] = $this->createBillingSetup();

        // Create an invoice for Rs. 40,000.00
        $this->actingAs($sales)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-05',
            'due_date' => '2026-10-20',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertSame('unpaid', $invoice->payment_status);

        // 1. Record partial payment of Rs. 15,000.00
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-06',
            'payment_method' => 'cash',
            'amount' => '15000.00',
            'reference_number' => 'REF-001',
        ])->assertRedirect(route('payments.index'));

        $this->assertSame('partial', $invoice->fresh()->payment_status);

        // 2. Attempt overpayment of Rs. 30,000.00 (remaining balance is 25,000.00) -> rejected
        $this->actingAs($sales)->from(route('payments.create'))->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-07',
            'payment_method' => 'bank_transfer',
            'amount' => '30000.00',
        ])->assertSessionHasErrors('amount');

        // 3. Record remaining Rs. 25,000.00 -> transitions invoice to 'paid'
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-10-08',
            'payment_method' => 'bank_transfer',
            'amount' => '25000.00',
            'reference_number' => 'REF-002',
        ])->assertRedirect(route('payments.index'));

        $this->assertSame('paid', $invoice->fresh()->payment_status);

        $secondPayment = Payment::latest('id')->firstOrFail();

        // 4. Non-Admin (Sales User) cannot reverse a payment (403)
        $this->actingAs($sales)->post(route('payments.reverse', $secondPayment), [
            'reversal_reason' => 'Unauthorized reversal attempt by sales user',
        ])->assertStatus(403);

        // 5. Admin reverses the second payment -> status becomes 'reversed' & invoice reverts to 'partial'
        $this->actingAs($admin)->post(route('payments.reverse', $secondPayment), [
            'reversal_reason' => 'Cheque dishonoured by bank verification',
        ])->assertRedirect(route('payments.show', $secondPayment));

        $this->assertSame('reversed', $secondPayment->fresh()->status);
        $this->assertSame($admin->id, $secondPayment->fresh()->reversed_by);
        $this->assertSame('partial', $invoice->fresh()->payment_status);
    }

    public function test_customer_statement_reconciles_opening_balance_invoiced_payments_and_closing_balance(): void
    {
        [$admin, $sales, $customer, $product] = $this->createBillingSetup();

        // Invoice 1 before filter period (2026-09-15): Rs. 40,000.00
        $this->actingAs($sales)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-09-15',
            'due_date' => '2026-09-30',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $invoice1 = Invoice::latest('id')->firstOrFail();

        // Payment 1 before filter period (2026-09-20): Rs. 10,000.00 -> Opening Balance on 2026-10-01 = Rs. 30,000.00
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice1->id,
            'payment_date' => '2026-09-20',
            'payment_method' => 'cash',
            'amount' => '10000.00',
        ]);

        // Invoice 2 inside filter period (2026-10-05): Rs. 80,000.00 (2 units)
        $this->actingAs($sales)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-05',
            'due_date' => '2026-10-20',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);
        $invoice2 = Invoice::latest('id')->firstOrFail();

        // Payment 2 inside filter period (2026-10-10): Rs. 25,000.00
        $this->actingAs($sales)->post(route('payments.store'), [
            'invoice_id' => $invoice2->id,
            'payment_date' => '2026-10-10',
            'payment_method' => 'online_payment',
            'amount' => '25000.00',
        ]);

        $service = app(CustomerStatementService::class);
        $statement = $service->buildStatement($customer, '2026-10-01', '2026-10-31');

        // Opening = 30000.00, Invoiced = 80000.00, Paid = 25000.00, Closing = 85000.00
        $this->assertEquals(30000.00, $statement['openingBalance']);
        $this->assertEquals(80000.00, $statement['periodInvoiced']);
        $this->assertEquals(25000.00, $statement['periodPaid']);
        $this->assertEquals(85000.00, $statement['closingBalance']);
        $this->assertCount(2, $statement['entries']);

        // Verify HTTP view & print endpoints
        $this->actingAs($sales)
            ->get(route('customers.statement', ['customer' => $customer->id, 'from_date' => '2026-10-01', 'to_date' => '2026-10-31']))
            ->assertStatus(200)
            ->assertSee('85,000.00');

        $this->actingAs($sales)
            ->get(route('customers.statement.print', ['customer' => $customer->id, 'from_date' => '2026-10-01', 'to_date' => '2026-10-31']))
            ->assertStatus(200)
            ->assertSee('Receivables Transaction Ledger')
            ->assertSee('85,000.00');
    }
}
