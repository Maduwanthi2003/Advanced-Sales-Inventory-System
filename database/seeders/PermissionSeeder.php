<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'customers',
            'suppliers',
            'categories',
            'products',
            'purchases',
            'invoices',
            'payments',
            'reports',
            'users_roles',
            'system_settings',
        ];

        $actions = [
            'view',
            'create',
            'edit',
            'delete',
        ];

        DB::transaction(function () use ($modules, $actions) {

            foreach ($modules as $module) {
                foreach ($actions as $action) {

                    Permission::firstOrCreate(
                        [
                            'name' => $module . '.' . $action,
                        ],
                        [
                            'module' => $module,
                            'action' => $action,
                        ]
                    );
                }
            }

            // Keep the existing Admin role and assign
            // all available permissions to it.

            $adminRole = Role::query()
                ->whereRaw('LOWER(name) = ?', ['admin'])
                ->first();

            if ($adminRole) {
                $permissionIds = Permission::pluck('id')->all();

                $adminRole->permissions()
                    ->syncWithoutDetaching($permissionIds);
            }

            // Default permissions for Sales User (only assigned if role has no permissions yet).
            $salesRole = Role::query()
                ->whereRaw('LOWER(name) = ?', ['sales user'])
                ->first();

            if ($salesRole && $salesRole->permissions()->doesntExist()) {
                $salesPermissionNames = [
                    'customers.view',
                    'customers.create',
                    'customers.edit',
                    'customers.delete',
                    'products.view',
                    'invoices.view',
                    'invoices.create',
                    'invoices.edit',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.edit',
                    'payments.delete',
                    'reports.view',
                ];

                $salesPermissionIds = Permission::query()
                    ->whereIn('name', $salesPermissionNames)
                    ->pluck('id')
                    ->all();

                $salesRole->permissions()->syncWithoutDetaching($salesPermissionIds);
            }

            // Default permissions for Inventory User (only assigned if role has no permissions yet).
            $inventoryRole = Role::query()
                ->whereRaw('LOWER(name) = ?', ['inventory user'])
                ->first();

            if ($inventoryRole && $inventoryRole->permissions()->doesntExist()) {
                $inventoryPermissionNames = [
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.delete',
                    'categories.view',
                    'categories.create',
                    'categories.edit',
                    'categories.delete',
                    'suppliers.view',
                    'suppliers.create',
                    'suppliers.edit',
                    'suppliers.delete',
                    'purchases.view',
                    'purchases.create',
                    'purchases.edit',
                    'purchases.delete',
                    'reports.view',
                ];

                $inventoryPermissionIds = Permission::query()
                    ->whereIn('name', $inventoryPermissionNames)
                    ->pluck('id')
                    ->all();

                $inventoryRole->permissions()->syncWithoutDetaching($inventoryPermissionIds);
            }
        });
    }
}
