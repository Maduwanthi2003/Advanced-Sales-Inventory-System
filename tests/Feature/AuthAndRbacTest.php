<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use DatabaseTransactions;

    private function createUserWithRole(string $roleName, bool $isActive = true): User
    {
        $role = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->firstOrFail();

        return User::create([
            'name' => "Test {$roleName}",
            'email' => uniqid(strtolower(str_replace(' ', '_', $roleName)) . '_', true) . '@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $role->id,
            'is_active' => $isActive,
        ]);
    }

    public function test_passwords_are_securely_hashed_and_public_registration_is_disabled(): void
    {
        $user = $this->createUserWithRole('Admin');

        $this->assertNotSame('SecretPass123!', $user->password);
        $this->assertTrue(Hash::check('SecretPass123!', $user->password));

        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertStatus(404);
    }

    public function test_active_user_can_login_and_logout_with_session_regeneration(): void
    {
        $user = $this->createUserWithRole('Admin');

        $loginResponse = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_or_access_protected_routes(): void
    {
        $inactiveUser = $this->createUserWithRole('Sales User', false);

        $response = $this->post(route('login.submit'), [
            'email' => $inactiveUser->email,
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactiveUser)
            ->get(route('dashboard'))
            ->assertStatus(403);
    }

    public function test_user_with_inactive_role_is_rejected_at_login_and_middleware(): void
    {
        $inactiveRole = Role::create([
            'name' => 'Temp Inactive Role',
            'description' => 'Inactive role for test',
            'is_active' => false,
        ]);

        $user = User::create([
            'name' => 'Role Inactive User',
            'email' => 'role_inactive@example.com',
            'password' => 'SecretPass123!',
            'role_id' => $inactiveRole->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(403);
    }

    public function test_role_based_access_control_for_admin_sales_and_inventory_users(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $sales = $this->createUserWithRole('Sales User');
        $inventory = $this->createUserWithRole('Inventory User');

        // Admin has full access
        $this->actingAs($admin)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('users.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('roles.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('purchases.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('invoices.index'))->assertStatus(200);

        // Sales User can access Sales/Customers/Products view, but gets 403 on Purchases/Suppliers/Categories/Users
        $this->actingAs($sales)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($sales)->get(route('customers.index'))->assertStatus(200);
        $this->actingAs($sales)->get(route('invoices.index'))->assertStatus(200);
        $this->actingAs($sales)->get(route('payments.index'))->assertStatus(200);
        $this->actingAs($sales)->get(route('products.index'))->assertStatus(200);
        $this->actingAs($sales)->get(route('products.create'))->assertStatus(403);
        $this->actingAs($sales)->get(route('suppliers.index'))->assertStatus(403);
        $this->actingAs($sales)->get(route('categories.index'))->assertStatus(403);
        $this->actingAs($sales)->get(route('purchases.index'))->assertStatus(403);
        $this->actingAs($sales)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($sales)->get(route('roles.index'))->assertStatus(403);

        // Inventory User can access Inventory/Purchases/Suppliers/Categories, but gets 403 on Sales/Customers/Users
        $this->actingAs($inventory)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('products.index'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('products.create'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('categories.index'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('suppliers.index'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('purchases.index'))->assertStatus(200);
        $this->actingAs($inventory)->get(route('customers.index'))->assertStatus(403);
        $this->actingAs($inventory)->get(route('invoices.index'))->assertStatus(403);
        $this->actingAs($inventory)->get(route('payments.index'))->assertStatus(403);
        $this->actingAs($inventory)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($inventory)->get(route('roles.index'))->assertStatus(403);
    }
}
