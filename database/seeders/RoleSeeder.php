<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Full system access',
            ],
            [
                'name' => 'Sales User',
                'description' => 'Sales and payment management',
            ],
            [
                'name' => 'Inventory User',
                'description' => 'Inventory and purchase management',
            ],
        ];

        foreach ($roles as $roleData) {
            $existingRole = Role::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($roleData['name'])])
                ->first();

            if (!$existingRole) {
                Role::create([
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_active' => true,
                ]);
            }
        }
    }
}

