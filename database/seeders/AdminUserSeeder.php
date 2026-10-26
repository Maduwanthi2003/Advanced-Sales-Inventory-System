<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->whereRaw('LOWER(name) = ?', ['admin'])
            ->first();

        if (!$adminRole) {
            throw new RuntimeException(
                'Admin role not found. Run RoleSeeder first.'
            );
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        // If an active Admin user already exists and credentials are not provided, skip safely.
        $hasActiveAdmin = User::query()
            ->where('role_id', $adminRole->id)
            ->where('is_active', true)
            ->exists();

        if ((!$email || !$password) && $hasActiveAdmin) {
            return;
        }

        if (!$email || !$password) {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env.'
            );
        }

        // Preserve existing user records and passwords if the email already exists.
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}
