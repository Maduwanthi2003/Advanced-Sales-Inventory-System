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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityAndValidationTest extends TestCase
{
    use DatabaseTransactions;

    private function createUserWithRole(string $roleName = 'Admin'): User
    {
        $role = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->firstOrFail();

        return User::create([
            'name' => "T10 {$roleName}",
            'email' => uniqid('t10_' . strtolower(str_replace(' ', '_', $roleName)) . '_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_phone_validation_accepts_valid_local_and_international_formats_trims_and_preserves_leading_zeros(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $role = Role::whereRaw('LOWER(name) = ?', ['sales user'])->firstOrFail();

        // 1. Customer: local number with leading zero and surrounding whitespace
        $customerResponse = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Lanka Traders',
            'contact_person' => 'Kasun Perera',
            'phone' => '  0771234567  ',
            'email' => uniqid('cust_', true) . '@example.com',
            'is_active' => '1',
        ]);
        $customerResponse->assertRedirect(route('customers.index'));

        $customer = Customer::where('name', 'Lanka Traders')->latest('id')->firstOrFail();
        $this->assertSame('0771234567', $customer->phone);

        // Update Customer with international +94 format
        $this->actingAs($admin)->put(route('customers.update', $customer), [
            'name' => 'Lanka Traders',
            'contact_person' => 'Kasun Perera',
            'phone' => '+94771234567',
            'email' => $customer->email,
            'is_active' => '1',
        ])->assertRedirect(route('customers.index'));
        $this->assertSame('+94771234567', $customer->fresh()->phone);

        // 2. Supplier: local landline with leading zero
        $supplierResponse = $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'Ceylon Supplies',
            'company_name' => 'Ceylon Supplies Pvt Ltd',
            'contact_person' => 'Nimal Silva',
            'phone' => ' 0112345678 ',
            'email' => uniqid('supp_', true) . '@example.com',
            'is_active' => '1',
        ]);
        $supplierResponse->assertRedirect(route('suppliers.index'));

        $supplier = Supplier::where('name', 'Ceylon Supplies')->latest('id')->firstOrFail();
        $this->assertSame('0112345678', $supplier->phone);

        // 3. User: international +94 format
        $userEmail = uniqid('staff_', true) . '@example.com';
        $userResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Staff Member',
            'email' => $userEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $role->id,
            'phone' => ' +94112345678 ',
            'is_active' => '1',
        ]);
        $userResponse->assertRedirect(route('users.index'));

        $createdUser = User::where('email', $userEmail)->firstOrFail();
        $this->assertSame('+94112345678', $createdUser->phone);
    }

    public function test_phone_validation_rejects_letters_invalid_symbols_and_out_of_range_lengths(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $role = Role::whereRaw('LOWER(name) = ?', ['sales user'])->firstOrFail();

        $invalidPhones = [
            '077ABC4567',        // alphabetic characters
            '077-123-4567',      // hyphens
            '++94771234567',     // multiple leading plus signs
            '+94+771234567',     // plus sign in middle
            '07712345',          // too short (8 digits)
            '0771234567890123',  // too long (16 digits)
        ];

        foreach ($invalidPhones as $invalidPhone) {
            $this->actingAs($admin)->post(route('customers.store'), [
                'name' => 'Invalid Customer',
                'contact_person' => 'Contact',
                'phone' => $invalidPhone,
                'email' => uniqid('inv_cust_', true) . '@example.com',
                'is_active' => '1',
            ])->assertSessionHasErrors('phone');

            $this->actingAs($admin)->post(route('suppliers.store'), [
                'name' => 'Invalid Supplier',
                'company_name' => 'Invalid Co',
                'contact_person' => 'Contact',
                'phone' => $invalidPhone,
                'email' => uniqid('inv_supp_', true) . '@example.com',
                'is_active' => '1',
            ])->assertSessionHasErrors('phone');

            $this->actingAs($admin)->post(route('users.store'), [
                'name' => 'Invalid User',
                'email' => uniqid('inv_usr_', true) . '@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role_id' => $role->id,
                'phone' => $invalidPhone,
                'is_active' => '1',
            ])->assertSessionHasErrors('phone');
        }
    }

    public function test_login_lockout_triggers_after_three_failures_blocks_valid_credentials_and_recovers_after_five_minutes(): void
    {
        $user = $this->createUserWithRole('Admin');
        $normalizedEmail = mb_strtolower($user->email, 'UTF-8');
        $accountKey = 'login:account:' . sha1($normalizedEmail . '|127.0.0.1');
        $ipKey = 'login:ip:127.0.0.1';

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);

        // Attempt 1 (failed): invalid credentials message, not locked out
        $res1 = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'WrongPassword1!',
        ]);
        $res1->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Invalid email or password.',
            session('errors')->first('email')
        );

        // Attempt 2 (failed): case-varied email shares the same normalized throttle bucket
        $res2 = $this->post(route('login.submit'), [
            'email' => '  ' . strtoupper($user->email) . '  ',
            'password' => 'WrongPassword2!',
        ]);
        $res2->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Invalid email or password.',
            session('errors')->first('email')
        );

        // Attempt 3 (failed): reaches 3 failed attempts -> triggers 5-minute lockout
        $res3 = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'WrongPassword3!',
        ]);
        $res3->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many failed login attempts',
            session('errors')->first('email')
        );
        $this->assertGuest();

        // Attempt 4 (WITH CORRECT PASSWORD during active lockout): MUST be blocked before Auth::attempt
        $blockedValid = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);
        $blockedValid->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many failed login attempts',
            session('errors')->first('email')
        );
        $this->assertGuest();

        // Another user from the same IP is NOT locked out by the first user's 3 account failures
        $otherUser = $this->createUserWithRole('Sales User');
        $otherLogin = $this->post(route('login.submit'), [
            'email' => $otherUser->email,
            'password' => 'SecretPass123!',
        ]);
        $otherLogin->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        // Travel past the 5-minute (300s) lockout window
        $this->travel(301)->seconds();

        $recoveredLogin = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);
        $recoveredLogin->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_single_active_session_invalidates_previous_session_and_protects_newer_session_on_stale_logout(): void
    {
        $user = $this->createUserWithRole('Admin');

        // Login #1 (Device A)
        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $deviceAToken = session('auth_session_token');
        $this->assertIsString($deviceAToken);
        $this->assertSame(64, strlen($deviceAToken));

        $userAfterLoginA = $user->fresh();
        $hashA = $userAfterLoginA->active_session_hash;
        $rememberA = $userAfterLoginA->remember_token;

        // Verify database stores SHA-256 hash and never the raw session token
        $this->assertNotSame($deviceAToken, $hashA);
        $this->assertSame(hash('sha256', $deviceAToken), $hashA);

        // Device A can access protected route while active
        $this->get(route('dashboard'))->assertStatus(200);

        // Simulate Login #2 (Device B) for the same user
        $deviceBToken = Str::random(64);
        $hashB = hash('sha256', $deviceBToken);
        $user->forceFill([
            'active_session_hash' => $hashB,
            'remember_token' => Str::random(60),
        ])->save();

        $this->assertNotSame($rememberA, $user->fresh()->remember_token);

        // Device A (still holding $deviceAToken in its session) makes a request -> MUST be rejected
        $supersededResponse = $this->withSession([
            'auth_session_token' => $deviceAToken,
        ])->get(route('dashboard'));

        $supersededResponse->assertRedirect(route('login'));
        $supersededResponse->assertSessionHas(
            'error',
            'Your account was signed in from another device. Please log in again.'
        );
        $this->assertGuest();

        // Verify Device B's active_session_hash was NOT cleared when Device A was rejected
        $this->assertSame($hashB, $user->fresh()->active_session_hash);

        // Verify stale logout from a superseded session does NOT clear Device B's active_session_hash
        \Illuminate\Support\Facades\Auth::login($user);
        $this->withSession([
            'auth_session_token' => $deviceAToken, // stale token
        ])->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame($hashB, $user->fresh()->active_session_hash);

        // Device B (holding $deviceBToken) continues to work normally
        \Illuminate\Support\Facades\Auth::login($user);
        $this->withSession([
            'auth_session_token' => $deviceBToken,
        ])->get(route('dashboard'))->assertStatus(200);

        // Active logout from Device B clears active_session_hash to null
        $this->withSession([
            'auth_session_token' => $deviceBToken,
        ])->post(route('logout'))->assertRedirect(route('login'));

        $this->assertNull($user->fresh()->active_session_hash);
    }

    public function test_check_permission_rejects_session_missing_token_or_null_database_hash(): void
    {
        $user = $this->createUserWithRole('Admin');

        // Case 1: Authenticated user has NULL active_session_hash in database
        $this->actingAs($user);
        $user->forceFill(['active_session_hash' => null])->saveQuietly();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account was signed in from another device. Please log in again.');

        // Case 2: Authenticated user has valid DB hash, but session is missing auth_session_token
        $this->actingAs($user);
        $this->withSession(['auth_session_token' => ''])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account was signed in from another device. Please log in again.');
    }

    public function test_financial_validation_enforces_max_decimal_bounds_and_two_decimal_places(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $category = Category::create([
            'name' => 'T10 Category ' . uniqid(),
            'description' => 'Test Category',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'T10 Supplier',
            'company_name' => 'T10 Supplier Co',
            'contact_person' => 'Supplier Person',
            'phone' => '0771234567',
            'email' => uniqid('t10_sup_', true) . '@example.com',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'T10 Customer',
            'contact_person' => 'Customer Person',
            'phone' => '0779876543',
            'email' => uniqid('t10_cus_', true) . '@example.com',
            'is_active' => true,
        ]);

        // 1. Product: > 9999999999999.99 and > 2 decimal places rejected
        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Overflow Product',
            'sku' => 'OVF-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '10000000000000.00',
            'selling_price' => '125.999',
            'quantity' => 10,
            'reorder_level' => 2,
            'is_active' => '1',
        ])->assertSessionHasErrors(['cost_price', 'selling_price']);

        $product = Product::create([
            'name' => 'High Value Product',
            'sku' => 'HVP-' . uniqid(),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost_price' => '5000000000000.00',
            'selling_price' => '6000000000000.00',
            'quantity' => 10,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        // 2. Purchase: > 2 decimal places on unit_cost and > max DECIMAL(15,2) rejected
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_cost' => '100.555',
                ],
            ],
        ])->assertSessionHasErrors('items');

        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_cost' => '10000000000000.00',
                ],
            ],
        ])->assertSessionHasErrors('items.0.unit_cost');

        // 3. Invoice: line total overflow (2 * 6,000,000,000,000.00 = 12,000,000,000,000.00 > DECIMAL(15,2)) rejected
        $this->actingAs($admin)->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => now()->format('Y-m-d'),
            'discount' => '0.00',
            'tax' => '0.00',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertSessionHasErrors('items');

        // 4. Payment: > 2 decimal places and > max DECIMAL(15,2) rejected
        $invoice = Invoice::create([
            'invoice_number' => 'INV-T10-' . uniqid(),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'invoice_date' => now()->format('Y-m-d'),
            'subtotal' => 1000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'grand_total' => 1000.00,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'amount' => '100.123',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'amount' => '10000000000000.00',
        ])->assertSessionHasErrors('amount');
    }
}
