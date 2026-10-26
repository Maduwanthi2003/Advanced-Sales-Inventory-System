
{{-- Category details and status fields. --}}
<div class="cf-fields">

    <div class="cf-field">
        <label for="name">
            Category Name <span>*</span>
        </label>

        <div class="cf-input-group">
            <span class="cf-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
                    <path d="m3 7 9 5 9-5M12 12v10"/>
                </svg>
            </span>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $category->name ?? '') }}"
                placeholder="Enter category name"
                maxlength="150"
                required
            >
        </div>

        @error('name')
            <small class="cf-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="cf-field">
        <label for="description">Description</label>

        <div class="cf-input-group cf-textarea-group">
            <span class="cf-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="5" y="3" width="14" height="18" rx="2"/>
                    <path d="M9 8h6M9 12h6M9 16h4"/>
                </svg>
            </span>

            <textarea
                id="description"
                name="description"
                rows="3"
                placeholder="Enter category description (optional)"
            >{{ old('description', $category->description ?? '') }}</textarea>
        </div>

        @error('description')
            <small class="cf-error">{{ $message }}</small>
        @enderror
    </div>

    <div class="cf-field">
        <label for="is_active">
            Status <span>*</span>
        </label>

        <x-status-toggle
            name="is_active"
            id="is_active"
            :value="old('is_active', isset($category) ? (int) $category->is_active : 1)"
        />

        @error('is_active')
            <small class="cf-error">{{ $message }}</small>
        @enderror
    </div>

</div>

{{-- Cancel or save the category. --}}
<div class="cf-actions">
    <a href="{{ route('categories.index') }}"
       class="cf-cancel">
        Cancel
    </a>

    <button type="submit" class="cf-save">
        <svg viewBox="0 0 24 24">
            <path d="M4 3h14l3 3v15H4zM8 3v6h9V3M8 21v-8h9v8"/>
        </svg>
        {{ $buttonText }}
    </button>
</div>
