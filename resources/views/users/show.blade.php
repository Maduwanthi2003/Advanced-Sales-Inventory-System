
{{-- User details and account status. --}}
@extends('layouts.app')

@section('title', 'View User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/user-show.css') }}?v=1">
@endpush

@section('content')

<div class="uv-page">

    <div class="uv-heading" style="justify-content: flex-end;">
        <div class="uv-heading-actions">
            <a href="{{ route('users.index') }}" class="uv-back-btn">
                ← Back to Users
            </a>

            <a href="{{ route('users.edit', $user) }}" class="uv-edit-btn">
                Edit User
            </a>
        </div>
    </div>

    <section class="uv-card">

        <div class="uv-profile">
            <div class="uv-avatar">
                @if($user->profile_image)
                    <img
                        src="{{ request()->getBaseUrl() . '/storage/' . ltrim($user->profile_image, '/') }}"
                        alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
            </div>

            <div>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>

                <span class="uv-status {{ $user->is_active ? 'active' : 'inactive' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div class="uv-section-title">
            <h3>User Information</h3>
            <p>Account and profile details</p>
        </div>

        <div class="uv-details">

            <div class="uv-field">
                <span>Full Name</span>
                <strong>{{ $user->name }}</strong>
            </div>

            <div class="uv-field">
                <span>Email Address</span>
                <strong>{{ $user->email }}</strong>
            </div>

            <div class="uv-field">
                <span>Role</span>
                <strong>{{ $user->role?->name ?? 'Unassigned' }}</strong>
            </div>

            <div class="uv-field">
                <span>Status</span>
                <strong>{{ $user->is_active ? 'Active' : 'Inactive' }}</strong>
            </div>

            <div class="uv-field">
                <span>Phone Number</span>
                <strong>{{ $user->phone ?: '—' }}</strong>
            </div>

            <div class="uv-field">
                <span>Department</span>
                <strong>{{ $user->department ?: '—' }}</strong>
            </div>

            <div class="uv-field">
                <span>Last Login</span>
                <strong>
                    {{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}
                </strong>
            </div>

            <div class="uv-field">
                <span>Created At</span>
                <strong>
                    {{ $user->created_at?->format('d M Y, h:i A') ?? '—' }}
                </strong>
            </div>

        </div>

        <div class="uv-footer">
            <a href="{{ route('users.index') }}" class="uv-back-btn">
                Cancel
            </a>

            <a href="{{ route('users.edit', $user) }}" class="uv-edit-btn">
                Edit User
            </a>
        </div>

    </section>

</div>

@endsection
