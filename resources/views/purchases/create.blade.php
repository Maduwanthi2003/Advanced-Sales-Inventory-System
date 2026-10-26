
{{-- Purchase details and line-item entry form. --}}
@extends('layouts.app')

@section('title', 'Add Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}?v=10">
@endpush

@section('content')
<div class="pu-page">



    @if($errors->any())
        <div class="pu-alert pu-error" role="alert">
            <strong>Please correct these errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="purchaseForm"
          action="{{ route('purchases.store') }}"
          method="POST">
        @csrf

        <!-- PURCHASE INFORMATION -->
        <section class="pu-card pu-section">

            <div class="pu-section-title">
                <h2>Purchase Information</h2>
                <p>Enter purchase details and add products to this purchase.</p>
            </div>

            <div class="pu-form-grid">

                <div class="pu-field">
                    <label for="purchaseNumber">
                        Purchase No <span>*</span>
                    </label>

                    <div class="pu-input-group">
                        <span class="pu-input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M6 3h9l4 4v14H6z"/>
                                <path d="M15 3v5h4M9 12h7M9 16h7"/>
                            </svg>
                        </span>
                        <input id="purchaseNumber"
                               type="text"
                               value="{{ $purchaseNumber ?? 'Assigned when saved' }}"
                               readonly>
                    </div>
                </div>

                <div class="pu-field">
                    <label for="supplier_id">
                        Supplier <span>*</span>
                    </label>

                    <div class="pu-input-group">
                        <span class="pu-input-icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2z"/>
                            </svg>
                        </span>

                        <select name="supplier_id"
                                id="supplier_id"
                                required>
                            <option value="">Select supplier</option>

                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    @selected(old('supplier_id') == $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pu-field">
                    <label for="purchase_date">
                        Purchase Date <span>*</span>
                    </label>

                    <div class="pu-input-group">
                        <span class="pu-input-icon">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="16" rx="2"/>
                                <path d="M7 3v4M17 3v4M3 10h18"/>
                            </svg>
                        </span>

                        <input type="date"
                               name="purchase_date"
                               id="purchase_date"
                               value="{{ old('purchase_date', date('Y-m-d')) }}"
                               required>
                    </div>
                </div>

                <div class="pu-field">
                    <label for="warehouse">Warehouse</label>

                    <div class="pu-input-group">
                        <span class="pu-input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 10l9-7 9 7v11H3z"/>
                                <path d="M9 21v-8h6v8"/>
                            </svg>
                        </span>

                        <select id="warehouse" disabled>
                            <option>Main Warehouse</option>
                        </select>
                    </div>
                </div>

                <div class="pu-field pu-full">
                    <label for="notes">Remarks (Optional)</label>

                    <div class="pu-input-group pu-textarea-group">
                        <span class="pu-input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M6 3h9l4 4v14H6z"/>
                                <path d="M9 12h7M9 16h7"/>
                            </svg>
                        </span>

                        <textarea name="notes"
                                  id="notes"
                                  rows="2"
                                  placeholder="Enter purchase remarks (optional)">{{ old('notes') }}</textarea>
                    </div>
                </div>

            </div>
        </section>

        <!-- PURCHASE ITEMS -->
        <section class="pu-card pu-section">

            <div class="pu-section-heading">
                <div>
                    <h2>Purchase Items</h2>
                    <p>Add products to this purchase.</p>
                </div>

                <button type="button"
                        class="pu-primary-btn"
                        id="addPurchaseItem">
                    <span>＋</span> Add Item
                </button>
            </div>

            <div class="pu-table-wrap">
                <table class="pu-table pu-items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Unit Cost (Rs.)</th>
                            <th>Quantity</th>
                            <th>Subtotal (Rs.)</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="purchaseItems"></tbody>
                </table>
            </div>

            <div class="pu-totals">
                <div>
                    <span>Subtotal (Rs.)</span>
                    <strong id="subtotal">0.00</strong>
                </div>

                <div>
                    <span>Total Items</span>
                    <strong id="totalItems">0</strong>
                </div>

                <div class="pu-grand">
                    <span>Total Amount (Rs.)</span>
                    <strong id="grandTotal">0.00</strong>
                </div>
            </div>

            <div class="pu-form-actions">
                <a href="{{ route('purchases.index') }}"
                   class="pu-cancel-btn">
                    Cancel
                </a>

                <button type="submit"
                        class="pu-primary-btn"
                        id="savePurchase">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 3h12l4 4v14H3V3z"/>
                        <path d="M7 3v7h10V3M7 21v-8h10v8"/>
                    </svg>
                    Save Purchase
                </button>
            </div>

        </section>
    </form>

    <!-- PRODUCT DATA -->
    <script type="application/json" id="purchaseProductsData">
{!! json_encode(
    $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'cost_price' => $product->cost_price,
        ];
    })->values()->all(),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}
    </script>

    <script type="application/json" id="purchaseOldItems">
{!! json_encode(
    old('items', []),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}
    </script>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/purchase-form.js') }}?v=11" defer></script>
@endpush
