<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    // Restrict user management to active administrators.
    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
                && (bool) $user->is_active
                && strcasecmp((string) $user->role?->name, 'Admin') === 0
                && (bool) $user->role?->is_active,
            403,
            'You are not authorized to access User Management.'
        );
    }

    // Check whether a user has the administrator role.
    private function isAdmin(User $user): bool
    {
        return strcasecmp(
            (string) $user->role?->name,
            'Admin'
        ) === 0;
    }

    // Protect the system from removing or deactivating its last active admin.
    private function ensureLastActiveAdminIsProtected(
        User $user,
        int $newRoleId,
        bool $newStatus
    ): void {
        $user->loadMissing('role');

        if (!$this->isAdmin($user) || !$user->is_active) {
            return;
        }

        $newRole = Role::findOrFail($newRoleId);

        if ($this->isAdminRole($newRole) && $newStatus) {
            return;
        }

        $activeAdminCount = User::query()
            ->where('is_active', true)
            ->whereHas('role', function (Builder $query) {
                $query->where('is_active', true)
                    ->whereRaw('LOWER(name) = ?', ['admin']);
            })
            ->count();

        if ($activeAdminCount <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'The last active administrator cannot be removed or deactivated.',
            ]);
        }
    }

    // Check whether a role is the protected Admin role.
    private function isAdminRole(Role $role): bool
    {
        return strcasecmp($role->name, 'Admin') === 0;
    }

    // List users with filters and management totals.
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $query = User::query()->with('role');

        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['status'])) {
            $query->where(
                'is_active',
                $filters['status'] === 'active'
            );
        }

        $baseQuery = clone $query;

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        if (!empty($filters['search'])) {
            $users->getCollection()->each(function ($user) use ($baseQuery) {
                $user->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '<', $user->id)->count() + 1
                );
            });
        }

        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        $adminUsers = User::whereHas('role', function (Builder $q) {
            $q->whereRaw('LOWER(name) = ?', ['admin']);
        })->count();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'adminUsers' => $adminUsers,
        ]);
    }

    // Show the user creation form with available roles.
    public function create(): View
    {
        $this->authorizeAdmin();

        return view('users.create', [
            'roles' => Role::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    // Validate and create a user account.
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($request->has('phone')) {
            $trimmedPhone = trim((string) $request->input('phone', ''));
            $request->merge([
                'phone' => $trimmedPhone !== '' ? $trimmedPhone : null,
            ]);
        }

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],
                'role_id' => [
                    'required',
                    Rule::exists('roles', 'id')->where('is_active', true),
                ],
                'is_active' => ['required', 'boolean'],
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8),
                ],
                'phone' => ['nullable', 'string', 'max:16', 'regex:/^\+?[0-9]{9,15}$/'],
                'department' => ['nullable', 'string', 'max:100'],
                'profile_image' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
            ],
            [
                'phone.regex' => 'The phone number must contain 9 to 15 digits and may start with a single leading + (e.g., 0771234567 or +94771234567).',
            ]
        );

        $imagePath = null;

        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')
                ->store('user-profiles', 'public');
        }

        try {
            DB::transaction(function () use ($validated, $imagePath) {
                $validated['profile_image'] = $imagePath;

                User::create($validated);
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    // Show the selected user's details.
    public function show(User $user): View
    {
        $this->authorizeAdmin();

        $user->load('role');

        return view('users.show', [
            'user' => $user,
        ]);
    }

    // Show the selected user's edit form.
    public function edit(User $user): View
    {
        $this->authorizeAdmin();

        $user->load('role');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()
                ->where('is_active', true)
                ->orWhere('id', $user->role_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    // Validate and update the selected user.
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($request->has('phone')) {
            $trimmedPhone = trim((string) $request->input('phone', ''));
            $request->merge([
                'phone' => $trimmedPhone !== '' ? $trimmedPhone : null,
            ]);
        }

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'role_id' => [
                    'required',
                    Rule::exists('roles', 'id')->where('is_active', true),
                ],
                'is_active' => ['required', 'boolean'],
                'password' => [
                    'nullable',
                    'confirmed',
                    Password::min(8),
                ],
                'phone' => ['nullable', 'string', 'max:16', 'regex:/^\+?[0-9]{9,15}$/'],
                'department' => ['nullable', 'string', 'max:100'],
                'profile_image' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
            ],
            [
                'phone.regex' => 'The phone number must contain 9 to 15 digits and may start with a single leading + (e.g., 0771234567 or +94771234567).',
            ]
        );

        if ((int) auth()->id() === (int) $user->id) {
            if (
                (int) $validated['role_id'] !== (int) $user->role_id ||
                !(bool) $validated['is_active']
            ) {
                throw ValidationException::withMessages([
                    'role_id' => 'You cannot change your own role or deactivate your account.',
                ]);
            }
        }

        $this->ensureLastActiveAdminIsProtected(
            $user,
            (int) $validated['role_id'],
            (bool) $validated['is_active']
        );

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $oldImage = $user->profile_image;
        $newImage = null;

        if ($request->hasFile('profile_image')) {
            $newImage = $request->file('profile_image')
                ->store('user-profiles', 'public');

            $validated['profile_image'] = $newImage;
        }

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update($validated);
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    // Delete a user when account protection rules allow it.
    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with(
                'error',
                'You cannot delete your own account.'
            );
        }

        $imagePath = $user->profile_image;

        DB::transaction(function () use ($user) {
            $user = User::query()
                ->with('role')
                ->lockForUpdate()
                ->findOrFail($user->id);

            if ($this->isAdmin($user) && $user->is_active) {
                $activeAdminCount = User::query()
                    ->where('is_active', true)
                    ->whereHas('role', function (Builder $query) {
                        $query->where('is_active', true)
                            ->whereRaw('LOWER(name) = ?', ['admin']);
                    })
                    ->count();

                if ($activeAdminCount <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'The last active administrator cannot be deleted.',
                    ]);
                }
            }

            $user->delete();
        });

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
