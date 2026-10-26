{{-- Shared customer details and status fields. --}}
<div class="customer-form-grid">

    <div class="customer-field">
        <label for="name">Customer Name <span>*</span></label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $customer->name ?? '') }}"
               maxlength="150"
               required>
        @error('name')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="customer-field">
        <label for="contact_person">Contact Person</label>
        <input type="text"
               id="contact_person"
               name="contact_person"
               value="{{ old('contact_person', $customer->contact_person ?? '') }}"
               maxlength="150">
        @error('contact_person')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="customer-field">
        <label for="phone">Phone Number</label>
        <input type="tel"
               id="phone"
               name="phone"
               inputmode="tel"
               pattern="^\+?[0-9]{9,15}$"
               title="Enter a valid phone number (9 to 15 digits, optional leading +)"
               value="{{ old('phone', $customer->phone ?? '') }}"
               maxlength="16">
        @error('phone')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="customer-field">
        <label for="email">Email Address</label>
        <input type="email"
               id="email"
               name="email"
               value="{{ old('email', $customer->email ?? '') }}">
        @error('email')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="customer-field">
        <label for="is_active">Status <span>*</span></label>
        <x-status-toggle
            name="is_active"
            id="is_active"
            :value="old('is_active', isset($customer) ? (int) $customer->is_active : 1)"
        />
        @error('is_active')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="customer-field customer-field-full">
        <label for="address">Address</label>
        <textarea id="address"
                  name="address"
                  rows="4">{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address')
            <small class="customer-field-error">{{ $message }}</small>
        @enderror
    </div>

</div>

<div class="customer-form-actions">
    <a href="{{ route('customers.index') }}"
       class="customer-cancel-btn">Cancel</a>

    <button type="submit" class="customer-primary-btn">
        {{ $buttonText }}
    </button>
</div>
