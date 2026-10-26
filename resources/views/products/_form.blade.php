{{-- Shared product details and inventory fields. --}}
<div class="pf-grid">
    <div class="pf-column">

        <div class="pf-field">
            <label for="name">Product Name <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
                        <path d="M3 7l9 5 9-5M12 12v10"/>
                    </svg>
                </span>
                <input id="name" name="name" type="text"
                       value="{{ old('name', $product->name ?? '') }}"
                       placeholder="Enter product name" maxlength="150" required>
            </div>
            @error('name') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="sku">Product Code <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 5h16v14H4z"/>
                        <path d="M8 8v8m3-8v8m3-8v8m3-8v8"/>
                    </svg>
                </span>
                <input id="sku" name="sku" type="text"
                       value="{{ old('sku', $product->sku ?? '') }}"
                       placeholder="Enter product code" maxlength="100" required>
            </div>
            @error('sku') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="category_id">Category <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </span>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('category_id') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="supplier_id">Supplier <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                    </svg>
                </span>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}"
                            @selected((string) old('supplier_id', $product->supplier_id ?? '') === (string) $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('supplier_id') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="cost_price">Cost Price (Rs.) <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M15 8.5c-.7-.8-1.7-1.2-3-1.2-1.7 0-2.8.8-2.8 2s1.1 1.8 2.8 2.2 2.8 1 2.8 2.3-1.1 2.1-2.8 2.1c-1.3 0-2.4-.4-3.2-1.3M12 5.5v13"/>
                    </svg>
                </span>
                <input id="cost_price" name="cost_price" type="number"
                       min="0" max="9999999999999.99" step="0.01"
                       value="{{ old('cost_price', $product->cost_price ?? '') }}"
                       placeholder="Enter cost price" required>
            </div>
            @error('cost_price') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="selling_price">Selling Price (Rs.) <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M15 8.5c-.7-.8-1.7-1.2-3-1.2-1.7 0-2.8.8-2.8 2s1.1 1.8 2.8 2.2 2.8 1 2.8 2.3-1.1 2.1-2.8 2.1c-1.3 0-2.4-.4-3.2-1.3M12 5.5v13"/>
                    </svg>
                </span>
                <input id="selling_price" name="selling_price" type="number"
                       min="0" max="9999999999999.99" step="0.01"
                       value="{{ old('selling_price', $product->selling_price ?? '') }}"
                       placeholder="Enter selling price" required>
            </div>
            @error('selling_price') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="quantity">Quantity (Stock) <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="m12 2 9 5v10l-9 5-9-5V7z"/>
                        <path d="M3 7l9 5 9-5M12 12v10"/>
                    </svg>
                </span>
                <input id="quantity" name="quantity" type="number"
                       min="0" step="1"
                       value="{{ old('quantity', $product->quantity ?? 0) }}"
                       placeholder="Enter quantity" required>
            </div>
            @error('quantity') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="reorder_level">Low Stock Alert Level <span>*</span></label>
            <div class="pf-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3v10m0 4v.1M4 21h16L12 3z"/>
                    </svg>
                </span>
                <input id="reorder_level" name="reorder_level" type="number"
                       min="0" step="1"
                       value="{{ old('reorder_level', $product->reorder_level ?? 5) }}"
                       required>
            </div>
            @error('reorder_level') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-field">
            <label for="is_active">Status <span>*</span></label>
            <x-status-toggle
                name="is_active"
                id="is_active"
                :value="old('is_active', isset($product) ? (int) $product->is_active : 1)"
            />
            @error('is_active') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

    </div>

    <div class="pf-column">
        <div class="pf-field">
            <label for="productImage">Product Image</label>

            <label class="pf-upload" for="productImage" id="uploadZone">
                <input id="productImage" name="image" type="file"
                       accept="image/png,image/jpeg" hidden>

                <img id="productPreview"
                     class="pf-preview"
                     src="{{ isset($product) && $product->image_path ? request()->getBaseUrl() . '/storage/' . ltrim($product->image_path, '/') : '' }}"
                     alt="Product image preview"
                     style="{{ isset($product) && $product->image_path ? '' : 'display:none' }}">

                <span class="pf-upload-instructions" id="uploadInstructions"
                      style="{{ isset($product) && $product->image_path ? 'display:none' : '' }}">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 16V4m0 0-5 5m5-5 5 5M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                    </svg>
                    <strong>Click to upload product image</strong>
                    <small>PNG, JPG, JPEG (Max 2MB)</small>
                </span>
            </label>

            @error('image') <small class="pf-error">{{ $message }}</small> @enderror
        </div>

        <div class="pf-image-note">
            Select an image to preview it before saving.
        </div>

        <div class="pf-field pf-description-field">
            <label for="description">Description</label>
            <div class="pf-control pf-textarea-control">
                <span class="pf-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 4h12M6 9h12M6 14h8M6 19h8"/>
                    </svg>
                </span>
                <textarea id="description" name="description" rows="4"
                          placeholder="Enter product description (optional)">{{ old('description', $product->description ?? '') }}</textarea>
            </div>
            @error('description') <small class="pf-error">{{ $message }}</small> @enderror
        </div>
    </div>
</div>

<div class="pf-actions">
    <a href="{{ route('products.index') }}" class="pf-cancel">Cancel</a>
    <button type="submit" class="pf-save">
        <svg viewBox="0 0 24 24">
            <path d="M4 3h14l3 3v15H4zM8 3v6h9V3M8 21v-8h9v8"/>
        </svg>
        {{ $buttonText }}
    </button>
</div>
