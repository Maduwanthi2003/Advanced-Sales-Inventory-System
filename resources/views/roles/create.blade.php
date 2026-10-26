
{{-- Role creation form and permission selection. --}}
@extends('layouts.app')

@section('title', 'Create Role')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/role-create.css') }}?v=2">
@endpush

@section('content')
<div class="create-role-page">



    @if($errors->any())
        <div class="create-role-errors" role="alert">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        id="createRoleForm"
        action="{{ route('roles.store') }}"
        method="POST"
    >
        @csrf

        <section class="create-role-card">
            <div class="create-role-section-heading">
                <h2>Role Information</h2>
                <p>Enter role details below.</p>
            </div>

            <div class="create-role-fields">
                <div class="create-role-field">
                    <label for="roleName">Role Name <span>*</span></label>

                    <div class="create-role-input-group">
                        <span class="create-role-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="m12 2 8 4v6c0 5-3.4 8.2-8 10-4.6-1.8-8-5-8-10V6l8-4Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="name"
                            id="roleName"
                            value="{{ old('name') }}"
                            placeholder="Enter role name (e.g. Sales User)"
                            maxlength="50"
                            required
                        >
                    </div>
                </div>

                <div class="create-role-field">
                    <label for="roleStatus">Status <span>*</span></label>

                    <x-status-toggle
                        name="is_active"
                        id="roleStatus"
                        :value="old('is_active', '1')"
                    />
                </div>

                <div class="create-role-field create-role-description-field">
                    <label for="roleDescription">Description</label>

                    <div class="create-role-input-group create-role-textarea-group">
                        <span class="create-role-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M7 3h8l4 4v14H7V3Z"/>
                                <path d="M15 3v5h4M10 12h6M10 16h6"/>
                            </svg>
                        </span>

                        <textarea
                            name="description"
                            id="roleDescription"
                            rows="2"
                            maxlength="255"
                            placeholder="Enter role description (e.g. Access to customers, invoices, payments and sales reports)"
                        >{{ old('description') }}</textarea>
                    </div>

                    <small class="create-role-character-count">
                        <span id="descriptionCount">0</span>/255
                    </small>
                </div>
            </div>
        </section>

        <section class="create-role-card create-role-permissions-section">
            <div class="create-role-section-heading create-role-permissions-heading">
                <div>
                    <h2>Module Permissions</h2>
                    <p>Select the permissions for this role. The user will have access to the selected modules and actions.</p>
                </div>

                <label class="create-role-select-all">
                    <input type="checkbox" id="selectAllPermissions">
                    Select All Permissions
                </label>
            </div>

            <div class="create-role-module-grid">
                @php
                    $moduleOrder = [
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

                    $actionOrder = ['view', 'create', 'edit', 'delete'];
                    $oldPermissions = array_map('strval', old('permissions', []));
                @endphp

                @foreach($moduleOrder as $module)
                    @php
                        $modulePermissions = collect($permissions[$module] ?? [])
                            ->sortBy(fn ($permission) =>
                                array_search($permission->action, $actionOrder, true) === false
                                    ? 999
                                    : array_search($permission->action, $actionOrder, true)
                            );

                        $moduleLabel = $module === 'users_roles'
                            ? 'Users & Roles'
                            : ucwords(str_replace('_', ' ', $module));
                    @endphp

                    <div class="create-role-module">
                        <div class="create-role-module-header">
                            <span class="create-role-module-icon" aria-hidden="true">▦</span>
                            <strong>{{ $moduleLabel }}</strong>
                        </div>

                        <div class="create-role-module-actions">
                            @foreach($modulePermissions as $permission)
                                <label>
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->id }}"
                                        class="create-role-permission-checkbox"
                                        @checked(in_array((string) $permission->id, $oldPermissions, true))
                                    >
                                    <span>{{ ucfirst($permission->action) }}</span>
                                </label>
                            @endforeach

                            @if($modulePermissions->isEmpty())
                                <span class="create-role-no-permissions">
                                    No permissions
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="create-role-footer">
                <a href="{{ route('roles.index') }}" class="create-role-cancel">
                    Cancel
                </a>

                <button type="submit" class="create-role-save" id="saveRoleButton">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 3h12l4 4v14H3V3h2Z"/>
                        <path d="M7 3v7h10V3M7 21v-8h10v8"/>
                    </svg>
                    Save Role
                </button>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/role-create.js') }}?v=2" defer></script>
@endpush
