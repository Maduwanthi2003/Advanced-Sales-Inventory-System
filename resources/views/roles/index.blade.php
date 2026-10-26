
{{-- Role list and permission management interface. --}}
@extends('layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/roles.css') }}?v=2">
@endpush

@section('content')
<div class="roles-page">



    @if(session('success'))
        <div class="roles-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="roles-alert error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="roles-alert error" role="alert">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="roles-summary">

        <div class="roles-summary-card blue">
            <div class="roles-summary-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m12 2 8 4v6c0 5-3.4 8.2-8 10-4.6-1.8-8-5-8-10V6l8-4Z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <div>
                <span>Total Roles</span>
                <h2>{{ $totalRoles }}</h2>
                <small>System roles</small>
            </div>
        </div>

        <div class="roles-summary-card green">
            <div class="roles-summary-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21v-2a8 8 0 0 1 16 0v2Z"/>
                </svg>
            </div>
            <div>
                <span>Total Users</span>
                <h2>{{ \App\Models\User::count() }}</h2>
                <small>Registered users</small>
            </div>
        </div>

        <div class="roles-summary-card purple">
            <div class="roles-summary-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>
                </svg>
            </div>
            <div>
                <span>Active Permissions</span>
                <h2>{{ $totalPermissions }}</h2>
                <small>Available permissions</small>
            </div>
        </div>

        <div class="roles-summary-card orange">
            <div class="roles-summary-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="3"/>
                    <path d="M6 20v-2a6 6 0 0 1 12 0v2M4 9a3 3 0 0 0-2 5M20 9a3 3 0 0 1 2 5"/>
                </svg>
            </div>
            <div>
                <span>Users with Roles</span>
                <h2>{{ \App\Models\User::whereNotNull('role_id')->count() }}</h2>
                <small>Assigned users</small>
            </div>
        </div>

    </div>

    <section class="roles-panel">

        <div class="roles-panel-header">
            <div>
                <h2>Roles List</h2>
                <p>Manage system roles and their permissions.</p>
            </div>

            <div class="roles-header-actions">

                <label class="roles-search">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m16 16 5 5"/>
                    </svg>

                    <input
                        type="search"
                        id="roleSearch"
                        placeholder="Search roles..."
                        aria-label="Search roles"
                    >
                </label>

                <a href="{{ route('roles.create') }}"
                   class="roles-primary-btn">
                    <span>＋</span> Add Role
                </a>

            </div>
        </div>

        <div class="roles-table-wrapper">
            <table class="roles-table" id="rolesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th>Users Count</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($roles as $role)
                        <tr class="role-data-row">
                            <td>{{ $loop->iteration }}</td>

                            <td class="roles-name">
                                {{ $role->name }}
                            </td>

                            <td>
                                {{ $role->description ?: '—' }}
                            </td>

                            <td>
                                <span class="roles-count">
                                    {{ $role->users_count }}
                                </span>
                            </td>

                            <td>
                                <span class="roles-status {{ $role->is_active ? 'active' : 'inactive' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                {{ $role->created_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            <td>
                                <div class="roles-row-actions">

                                    <button
                                        type="button"
                                        class="roles-icon-btn view"
                                        data-view-role="{{ $role->id }}"
                                        title="View Permissions"
                                        aria-label="View {{ $role->name }} permissions"
                                    >
                                        <svg viewBox="0 0 24 24">
                                            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    <a
                                        href="{{ route('roles.edit', $role) }}"
                                        class="roles-icon-btn edit"
                                        title="Edit Role"
                                        aria-label="Edit {{ $role->name }}"
                                    >
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9M16 4l4 4-11 11-5 1 1-5L16 4Z"/>
                                        </svg>
                                    </a>

                                    @if(strcasecmp($role->name, 'Admin') !== 0 && $role->users_count === 0)

                                        <form
                                            action="{{ route('roles.destroy', $role) }}"
                                            method="POST"
                                            class="roles-delete-form"
                                            data-role-name="{{ $role->name }}"
                                            data-confirm-delete
                                            data-delete-type="role"
                                            data-delete-name="{{ $role->name }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="roles-icon-btn delete"
                                                title="Delete Role"
                                                aria-label="Delete {{ $role->name }}"
                                            >
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/>
                                                </svg>
                                            </button>
                                        </form>

                                    @else

                                        <button
                                            type="button"
                                            class="roles-icon-btn delete"
                                            disabled
                                            title="Role cannot be deleted"
                                        >
                                            <svg viewBox="0 0 24 24">
                                                <path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/>
                                            </svg>
                                        </button>

                                    @endif

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="roles-empty">
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    <section class="roles-panel roles-permissions-panel">

        <div class="roles-panel-header">
            <div>
                <h2>Role Permissions</h2>
                <p>Select and manage permissions for the selected role.</p>
            </div>

            <div class="roles-permission-actions">

                <label class="roles-select-all">
                    <input
                        type="checkbox"
                        id="rolesSelectAll"
                        disabled
                    >
                    Select All
                </label>

                <button
                    type="button"
                    class="roles-primary-btn"
                    id="rolesSavePermissions"
                    disabled
                >
                    Save Permissions
                </button>

            </div>
        </div>

        <div class="roles-role-selector">
            <label for="selectedRole">
                Select Role <span>*</span>
            </label>

            <select id="selectedRole">
                <option value="">Select a role</option>

                @foreach($rolePermissions as $role)
                    <option value="{{ $role->id }}">
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="roles-permissions-grid"
             id="rolesPermissionsGrid">

            @forelse($permissions as $module => $modulePermissions)

                <div class="roles-module-card">

                    <div class="roles-module-heading">
                        <span class="roles-module-symbol">▦</span>

                        {{ $module === 'users_roles'
                            ? 'Users & Roles'
                            : ucwords(str_replace('_', ' ', $module)) }}
                    </div>

                    <div class="roles-module-options">

                        @foreach($modulePermissions as $permission)

                            <label>
                                <input
                                    type="checkbox"
                                    class="roles-permission-checkbox"
                                    value="{{ $permission->id }}"
                                    disabled
                                >

                                {{ ucwords(str_replace('_', ' ', $permission->action)) }}
                            </label>

                        @endforeach

                    </div>
                </div>

            @empty
                <p class="roles-empty">
                    No permissions available.
                    Permission records must be created first.
                </p>
            @endforelse

        </div>

        {{-- Save Permissions Form --}}
        <form
            id="rolePermissionsForm"
            method="POST"
            action="{{ url('/roles/0/permissions') }}"
            hidden
        >
            @csrf

            <div id="selectedPermissionInputs"></div>
        </form>

    </section>

    <script type="application/json" id="rolesPermissionData">
        @json($rolePermissions->mapWithKeys(fn ($role) => [
            $role->id => $role->permissions->pluck('id')->values()
        ]))
    </script>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/roles.js') }}?v=3" defer></script>
@endpush
