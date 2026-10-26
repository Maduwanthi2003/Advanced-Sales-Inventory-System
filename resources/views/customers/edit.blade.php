
{{-- Customer details and status editing form. --}}
@extends('layouts.app')

@section('title', 'Edit Customer')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-create.css') }}?v=1">
@endpush

@section('content')

<div class="cc-page">



    <section class="cc-card">

        <div class="cc-card-heading">
            <h2>Customer Information</h2>
            <p>Update the existing customer details.</p>
        </div>

        @if($errors->any())
            <div class="cc-error-summary" role="alert">
                Please correct the highlighted fields.
            </div>
        @endif

        <form action="{{ route('customers.update', $customer) }}"
              method="POST"
              class="cc-form">

            @csrf
            @method('PUT')

            <div class="cc-form-grid">

                <div class="cc-column">

                    <div class="cc-field">
                        <label for="name">Customer Name <span>*</span></label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                                </svg>
                            </span>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name', $customer->name) }}"
                                   placeholder="Enter customer name" required>
                        </div>
                        @error('name')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="contact_person">Contact Person <span>*</span></label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                                </svg>
                            </span>
                            <input type="text" id="contact_person" name="contact_person"
                                   value="{{ old('contact_person', $customer->contact_person) }}"
                                   placeholder="Enter contact person name" required>
                        </div>
                        @error('contact_person')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="phone">Phone <span>*</span></label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3h4l2 5-3 2a16 16 0 0 0 5 5l2-3 5 2v4a3 3 0 0 1-3 3C9 21 3 15 3 6a3 3 0 0 1 3-3z"/>
                                </svg>
                            </span>
                            <input type="tel" id="phone" name="phone"
                                   inputmode="tel"
                                   pattern="^\+?[0-9]{9,15}$"
                                   title="Enter a valid phone number (9 to 15 digits, optional leading +)"
                                   maxlength="16"
                                   value="{{ old('phone', $customer->phone) }}"
                                   placeholder="Enter phone number (e.g. 0771234567 or +94771234567)" required>
                        </div>
                        @error('phone')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="email">Email <span>*</span></label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                    <path d="m3 7 9 7 9-7"/>
                                </svg>
                            </span>
                            <input type="email" id="email" name="email"
                                   value="{{ old('email', $customer->email) }}"
                                   placeholder="Enter email address" required>
                        </div>
                        @error('email')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="is_active">Status <span>*</span></label>
                        <x-status-toggle
                            name="is_active"
                            id="is_active"
                            :value="old('is_active', (int) $customer->is_active)"
                        />
                    </div>

                </div>

                <div class="cc-column">

                    <div class="cc-field">
                        <label for="address">Address</label>
                        <div class="cc-control cc-textarea-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/>
                                    <circle cx="12" cy="10" r="2.5"/>
                                </svg>
                            </span>
                            <textarea id="address" name="address" rows="3"
                                      placeholder="Enter address">{{ old('address', $customer->address) }}</textarea>
                        </div>
                        @error('address')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="city">City</label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 21V8h7v13M11 21V3h9v18M7 11h1M7 15h1M15 7h1M15 11h1M15 15h1"/>
                                </svg>
                            </span>
                            <input type="text" id="city" name="city"
                                   value="{{ old('city', $customer->city) }}"
                                   placeholder="Enter city">
                        </div>
                        @error('city')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="postal_code">ZIP / Postal Code</label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <rect x="4" y="5" width="16" height="15" rx="2"/>
                                    <path d="M4 10h16M9 15h6"/>
                                </svg>
                            </span>
                            <input type="text" id="postal_code" name="postal_code"
                                   value="{{ old('postal_code', $customer->postal_code) }}"
                                   placeholder="Enter postal code">
                        </div>
                        @error('postal_code')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="country">Country</label>
                        <div class="cc-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M3 12h18M12 3c-5 5-5 13 0 18M12 3c5 5 5 13 0 18"/>
                                </svg>
                            </span>
                            <input type="text" id="country" name="country"
                                   value="{{ old('country', $customer->country) }}"
                                   placeholder="Enter country">
                        </div>
                        @error('country')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="cc-field">
                        <label for="notes">Notes</label>
                        <div class="cc-control cc-textarea-control">
                            <span class="cc-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3h9l4 4v14H6zM15 3v5h4M9 12h7M9 16h7"/>
                                </svg>
                            </span>
                            <textarea id="notes" name="notes" rows="2"
                                      placeholder="Enter additional notes (optional)">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                        @error('notes')
                            <small class="cc-error">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="cc-actions">
                <a href="{{ route('customers.index') }}"
                   class="cc-cancel-btn">
                    Cancel
                </a>

                <button type="submit" class="cc-save-btn">
                    Update Customer
                </button>
            </div>

        </form>

    </section>

</div>

@endsection
