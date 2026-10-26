
{{-- User list, filters, and management actions. --}}
@extends('layouts.app')

@section('title', 'All Users')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}?v=2">
@endpush

@section('content')

<div class="um-page">



    @if(session('success'))
        <div class="um-alert um-alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="um-alert um-alert-error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="um-alert um-alert-error" role="alert">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="um-stats">

        <div class="um-stat">
            <div class="um-stat-icon blue">👥</div>
            <div>
                <span>Total Users</span>
                <h2>{{ number_format($totalUsers) }}</h2>
                <small>Registered users</small>
            </div>
        </div>

        <div class="um-stat">
            <div class="um-stat-icon green">✓</div>
            <div>
                <span>Active Users</span>
                <h2>{{ number_format($activeUsers) }}</h2>
                <small>Active accounts</small>
            </div>
        </div>

        <div class="um-stat">
            <div class="um-stat-icon orange">◷</div>
            <div>
                <span>Inactive Users</span>
                <h2>{{ number_format($inactiveUsers) }}</h2>
                <small>Inactive accounts</small>
            </div>
        </div>

        <div class="um-stat">
            <div class="um-stat-icon purple">♛</div>
            <div>
                <span>Admin Users</span>
                <h2>{{ number_format($adminUsers) }}</h2>
                <small>Administrator accounts</small>
            </div>
        </div>

    </div>

    <section class="um-panel">

        <div class="um-panel-heading">
            <div>
                <h2>User Management</h2>
                <p>View and manage registered users.</p>
            </div>

            <a href="{{ route('users.create') }}" class="users-add-btn">
                + Add New User
            </a>
        </div>

        <form action="{{ route('users.index') }}"
              method="GET"
              class="um-filters">

            <div class="um-search">
                <input
                    type="search"
                    name="search"
                    placeholder="Search by name, email or department..."
                    value="{{ request('search') }}"
                >
            </div>

            <select name="role_id">
                <option value="">All Roles</option>

                @foreach($roles as $role)
                    <option value="{{ $role->id }}"
                        @selected((string) request('role_id') === (string) $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                <option value="active"
                    @selected(request('status') === 'active')>
                    Active
                </option>
                <option value="inactive"
                    @selected(request('status') === 'inactive')>
                    Inactive
                </option>
            </select>

            <button type="submit" class="um-filter-button">
                Filter
            </button>

            <a href="{{ route('users.index') }}"
               class="um-reset-button">
                Reset
            </a>
        </form>

        <div class="um-table-scroll">
            <table class="um-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        @php($profileImageUrl = $user->profile_image_url)
                        <tr>
                            <td>
                                {{ $user->list_serial ?? ($users->firstItem() + $loop->index) }}
                            </td>

                            <td>
                                <div class="um-user">
                                    <div class="um-avatar">
                                        @if($profileImageUrl)
                                            <img
                                                src="{{ $profileImageUrl }}"
                                                alt="{{ $user->name }}"
                                                onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                            <span hidden>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        @else
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        @endif
                                    </div>

                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <span>{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="um-role">
                                    {{ $user->role?->name ?? 'Unassigned' }}
                                </span>
                            </td>

                            <td>{{ $user->department ?? '—' }}</td>

                            <td>
                                <span class="um-status {{ $user->is_active ? 'active' : 'inactive' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                {{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}
                            </td>

                            <td>
                                <div class="um-actions">

                                    {{-- View User --}}
                                    <a
                                        href="{{ route('users.show', $user) }}"
                                        class="um-action-btn um-view-btn"
                                        title="View User"
                                        aria-label="View {{ $user->name }}"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    {{-- Edit User --}}
                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        class="um-action-btn um-edit-btn"
                                        title="Edit User"
                                        aria-label="Edit {{ $user->name }}"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    {{-- Delete User --}}
                                    @if(auth()->id() !== $user->id)
                                        <form
                                            action="{{ route('users.destroy', $user) }}"
                                            method="POST"
                                            class="um-delete-form"
                                            data-user-name="{{ $user->name }}"
                                            data-confirm-delete
                                            data-delete-type="user"
                                            data-delete-name="{{ $user->name }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="um-action-btn um-delete-btn"
                                                title="Delete User"
                                                aria-label="Delete {{ $user->name }}"
                                            >
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M3 6h18M8 6V4h8v2M5 6l1 15h12l1-15"/>
                                                    <path d="M10 10v7M14 10v7"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <button
                                            type="button"
                                            class="um-action-btn um-delete-btn"
                                            title="You cannot delete your own account"
                                            disabled
                                        >
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M3 6h18M8 6V4h8v2M5 6l1 15h12l1-15"/>
                                                <path d="M10 10v7M14 10v7"/>
                                            </svg>
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="um-empty">
                                No users found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="um-footer">
            <span>
                Showing {{ $users->firstItem() ?? 0 }}
                to {{ $users->lastItem() ?? 0 }}
                of {{ $users->total() }} entries
            </span>

            <div class="um-pagination">
                {{ $users->links() }}
            </div>
        </div>

    </section>

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/list-search.js') }}?v=1" defer></script>
@endpush
