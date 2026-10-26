
{{-- User creation form and profile image upload. --}}
@extends('layouts.app')

@section('title', 'Add User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/user-create.css') }}?v=2">
@endpush

@section('content')
<div class="uc-page">



    @if ($errors->any())
        <div class="uc-errors" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('users.store') }}"
        method="POST"
        enctype="multipart/form-data"
        id="createUserForm"
    >
        @csrf

        <section class="uc-card">
            <div class="uc-card-heading">
                <h2>User Information</h2>
                <p>Enter user details below.</p>
            </div>

            <div class="uc-grid">

                <div class="uc-field">
                    <label for="name">Full Name <em>*</em></label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                        </span>
                        <input id="name" name="name" type="text"
                               value="{{ old('name') }}"
                               placeholder="Enter full name" required>
                    </div>
                </div>

                <div class="uc-field">
                    <label for="role_id">Role <em>*</em></label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M2 20v-2a7 7 0 0 1 14 0v2M17 8h5M19.5 5.5v5"/></svg>
                        </span>
                        <select id="role_id" name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}"
                                    @selected(old('role_id') == $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="uc-field">
                    <label for="email">Email Address <em>*</em></label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 7 9-7"/></svg>
                        </span>
                        <input id="email" name="email" type="email"
                               value="{{ old('email') }}"
                               placeholder="Enter email address" required>
                    </div>
                </div>

                <div class="uc-field">
                    <label for="is_active">Status <em>*</em></label>
                    <x-status-toggle
                        name="is_active"
                        id="is_active"
                        :value="old('is_active', '1')"
                    />
                </div>

                <div class="uc-field">
                    <label for="password">Password <em>*</em></label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <input id="password" name="password"
                               type="password" minlength="8"
                               placeholder="Enter password" required>
                        <button class="uc-password-toggle" type="button"
                                data-password-target="password"
                                aria-label="Show password"
                                aria-pressed="false">
                            <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="uc-field">
                    <label for="phone">Phone Number (Optional)</label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><path d="M6 3h4l2 5-3 2a15 15 0 0 0 5 5l2-3 5 2v4a3 3 0 0 1-3 3A15 15 0 0 1 3 6a3 3 0 0 1 3-3Z"/></svg>
                        </span>
                        <input id="phone" name="phone" type="tel"
                               inputmode="tel"
                               pattern="^\+?[0-9]{9,15}$"
                               title="Enter a valid phone number (9 to 15 digits, optional leading +)"
                               maxlength="16"
                               value="{{ old('phone') }}"
                               placeholder="Enter phone number (e.g. 0771234567 or +94771234567)">
                    </div>
                    @error('phone')
                        <small class="uc-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="uc-field">
                    <label for="password_confirmation">
                        Confirm Password <em>*</em>
                    </label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <input id="password_confirmation"
                               name="password_confirmation"
                               type="password" minlength="8"
                               placeholder="Confirm password" required>
                        <button class="uc-password-toggle" type="button"
                                data-password-target="password_confirmation"
                                aria-label="Show password"
                                aria-pressed="false">
                            <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="uc-field">
                    <label for="department">Department (Optional)</label>
                    <div class="uc-input-wrap">
                        <span class="uc-input-icon">
                            <svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2m2 0h2M9 11h2m2 0h2M9 15h6"/></svg>
                        </span>
                        <input id="department" name="department"
                               type="text" value="{{ old('department') }}"
                               placeholder="Enter department"
                               list="departmentOptions">
                        <datalist id="departmentOptions">
                            <option value="Sales">
                            <option value="Inventory">
                            <option value="Administration">
                            <option value="Finance">
                        </datalist>
                    </div>
                </div>

            </div>
        </section>

        <section class="uc-card uc-profile-card">
            <div class="uc-card-heading">
                <h2>User Profile Image <span>(Optional)</span></h2>
                <p>Upload a profile image for the user.</p>
            </div>

            <div class="uc-upload-layout">
                <div class="uc-upload-zone" id="ucDropZone">
                    <input type="file" id="profile_image"
                           name="profile_image"
                           accept=".jpg,.jpeg,.png,.webp"
                           class="uc-file-input">

                    <div class="uc-upload-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 16V5m-4 4 4-4 4 4"/><path d="M5 17a5 5 0 0 1 1-9 7 7 0 0 1 13 2 4 4 0 0 1-1 8h-2"/></svg>
                    </div>

                    <div class="uc-upload-copy">
                        <strong>Drag &amp; drop an image here</strong>
                        <span>or click to browse</span>
                        <small>PNG, JPG or WebP (Max 2MB)</small>
                    </div>
                </div>

                <div class="uc-preview-area">
                    <strong>Preview</strong>
                    <div class="uc-preview-avatar">
                        <img id="ucImagePreview"
                             alt="Profile image preview"
                             hidden>
                        <svg id="ucDefaultAvatar" viewBox="0 0 64 64"
                             aria-hidden="true">
                            <circle cx="32" cy="32" r="32" fill="#dbe8ff"/>
                            <circle cx="32" cy="24" r="12" fill="#145cd5"/>
                            <path d="M11 55c3-12 12-18 21-18s18 6 21 18"
                                  fill="#145cd5"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="uc-actions">
                <a href="{{ route('users.index') }}" class="uc-cancel">
                    Cancel
                </a>

                <button type="submit" class="uc-save">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 3h12l4 4v14H3V3h2Z"/>
                        <path d="M7 3v7h10V3M7 21v-8h10v8"/>
                    </svg>
                    Save User
                </button>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/user-create.js') }}?v=2" defer></script>
@endpush
