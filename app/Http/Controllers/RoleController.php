<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Authorization
    |--------------------------------------------------------------------------
    */

    // Restrict role management to active administrators.
    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
                && (bool) $user->is_active
                && strcasecmp(
                    (string) $user->role?->name,
                    'Admin'
                ) === 0
                && (bool) $user->role?->is_active,
            403,
            'You are not authorized to manage roles.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Module Order
    |--------------------------------------------------------------------------
    */

    // Return permissions grouped in the configured display order.
    private function getGroupedPermissions()
    {
        $moduleOrder = [
            'customers',
            'suppliers',
            'products',
            'purchases',
            'invoices',
            'payments',
            'reports',
            'users_roles',
            'categories',
            'system_settings',
        ];

        $actionOrder = [
            'view',
            'create',
            'edit',
            'delete',
        ];

        return Permission::query()
            ->get()
            ->sortBy(function ($permission) use (
                $moduleOrder,
                $actionOrder
            ) {
                $moduleIndex = array_search(
                    $permission->module,
                    $moduleOrder,
                    true
                );

                $actionIndex = array_search(
                    $permission->action,
                    $actionOrder,
                    true
                );

                return sprintf(
                    '%03d-%03d-%s',
                    $moduleIndex === false ? 999 : $moduleIndex,
                    $actionIndex === false ? 999 : $actionIndex,
                    $permission->name
                );
            })
            ->groupBy('module');
    }

    /*
    |--------------------------------------------------------------------------
    | Role Name Validation
    |--------------------------------------------------------------------------
    */

    // Build role-name validation rules for creation or editing.
    private function roleNameRules(?Role $role = null): array
    {
        $uniqueRule = Rule::unique('roles', 'name');

        if ($role) {
            $uniqueRule->ignore($role->id);
        }

        return [
            'required',
            'string',
            'max:50',
            $uniqueRule,
            function ($attribute, $value, $fail) use ($role) {
                $name = trim((string) $value);

                if (strcasecmp($name, 'Admin') === 0) {
                    $fail('The Admin role name is reserved.');
                    return;
                }

                $existing = Role::query()
                    ->whereRaw(
                        'LOWER(name) = ?',
                        [strtolower($name)]
                    )
                    ->when(
                        $role,
                        fn ($query) => $query->where(
                            'id',
                            '!=',
                            $role->id
                        )
                    )
                    ->exists();

                if ($existing) {
                    $fail('A role with this name already exists.');
                }
            },
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | All Roles
    |--------------------------------------------------------------------------
    */

    // List roles and their assigned permissions.
    public function index(): View
    {
        $this->authorizeAdmin();

        $roles = Role::query()
            ->withCount([
                'users',
                'permissions',
            ])
            ->orderBy('id', 'asc')
            ->get();

        $rolePermissions = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles' => $roles,

            'permissions' => $this->getGroupedPermissions(),

            'rolePermissions' => $rolePermissions,

            'totalRoles' => Role::count(),

            'totalUsers' => User::count(),

            'usersWithRoles' => User::query()
                ->whereNotNull('role_id')
                ->count(),

            'activeRoles' => Role::query()
                ->where('is_active', true)
                ->count(),

            'inactiveRoles' => Role::query()
                ->where('is_active', false)
                ->count(),

            'totalPermissions' => Permission::count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Role Page
    |--------------------------------------------------------------------------
    */

    // Show the role creation form.
    public function create(): View
    {
        $this->authorizeAdmin();

        return view('roles.create', [
            'permissions' => $this->getGroupedPermissions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Role
    |--------------------------------------------------------------------------
    */

    // Validate and create a role with its selected permissions.
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => $this->roleNameRules(),

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'permissions' => [
                'sometimes',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id'),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => trim($validated['name']),

                'description' =>
                    $validated['description'] ?? null,

                'is_active' => $validated['is_active'],
            ]);

            $role->permissions()->sync(
                $validated['permissions'] ?? []
            );
        });

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Role Page
    |--------------------------------------------------------------------------
    */

    // Show the selected role and its permissions for editing.
    public function edit(Role $role): View
    {
        $this->authorizeAdmin();

        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,

            'permissions' => $this->getGroupedPermissions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Role
    |--------------------------------------------------------------------------
    */

    // Validate and update a role and its permission assignments.
    public function update(
        Request $request,
        Role $role
    ): RedirectResponse {
        $this->authorizeAdmin();

        // The system Admin role is protected.
        if (strcasecmp($role->name, 'Admin') === 0) {
            throw ValidationException::withMessages([
                'role' => 'The protected Admin role cannot be edited.',
            ]);
        }

        $validated = $request->validate([
            'name' => $this->roleNameRules($role),

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'permissions' => [
                'sometimes',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id'),
            ],
        ]);

        DB::transaction(function () use ($role, $validated) {
            $lockedRole = Role::query()
                ->lockForUpdate()
                ->findOrFail($role->id);

            // Recheck protection after locking.
            if (
                strcasecmp(
                    $lockedRole->name,
                    'Admin'
                ) === 0
            ) {
                throw ValidationException::withMessages([
                    'role' => 'The protected Admin role cannot be edited.',
                ]);
            }

            $newName = trim($validated['name']);

            if (strcasecmp($newName, 'Admin') === 0) {
                throw ValidationException::withMessages([
                    'name' => 'The Admin role name is reserved.',
                ]);
            }

            $lockedRole->update([
                'name' => $newName,

                'description' =>
                    $validated['description'] ?? null,

                'is_active' => $validated['is_active'],
            ]);

            $lockedRole->permissions()->sync(
                $validated['permissions'] ?? []
            );
        });

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Role
    |--------------------------------------------------------------------------
    */

    // Delete a role when its protection rules allow it.
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($role) {
            $lockedRole = Role::query()
                ->lockForUpdate()
                ->findOrFail($role->id);

            if (
                strcasecmp(
                    $lockedRole->name,
                    'Admin'
                ) === 0
            ) {
                throw ValidationException::withMessages([
                    'role' => 'The Admin role cannot be deleted.',
                ]);
            }

            if ($lockedRole->users()->exists()) {
                throw ValidationException::withMessages([
                    'role' => 'This role is assigned to users and cannot be deleted.',
                ]);
            }

            $lockedRole->permissions()->detach();

            $lockedRole->delete();
        });

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role deleted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Role Permissions
    |--------------------------------------------------------------------------
    */

    // Update permission assignments for the selected role.
    public function updatePermissions(
        Request $request,
        Role $role
    ): RedirectResponse {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'permissions' => [
                'sometimes',
                'array',
            ],

            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id'),
            ],
        ]);

        DB::transaction(function () use ($role, $validated) {
            $lockedRole = Role::query()
                ->lockForUpdate()
                ->findOrFail($role->id);

            // Admin permissions must remain protected.
            if (
                strcasecmp(
                    $lockedRole->name,
                    'Admin'
                ) === 0
            ) {
                throw ValidationException::withMessages([
                    'role' => 'Admin role permissions cannot be modified.',
                ]);
            }

            $lockedRole->permissions()->sync(
                $validated['permissions'] ?? []
            );
        });

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role permissions updated successfully.'
            );
    }
}
