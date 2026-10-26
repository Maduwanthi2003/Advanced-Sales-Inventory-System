{{-- Invoice details and line-item creation form. --}}
@extends('layouts.app')

@section('title', 'Add Sales Invoice')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/invoices.css') }}?v=1">
@endpush

@section('content')
<div class="iv-page">


    @if($errors->any())
        <div class="iv-alert iv-alert-error">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="invoiceForm"
          action="{{ route('invoices.store') }}"
          method="POST">
        @csrf

        <section class="iv-card iv-form-card">
            <div class="iv-section-heading">
                <h2>Invoice Information</h2>
                <p>Enter customer details and invoice information.</p>
            </div>

            <div class="iv-form-grid">
                <div class="iv-field">
                    <label>Invoice No</label>
                    <input type="text" value="Assigned when saved" readonly>
                </div>

                <div class="iv-field">
                    <label for="customer_id">Customer <b>*</b></label>
                    <select name="customer_id" id="customer_id" required>
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="iv-field">
                    <label>Sales Person</label>
                    <input type="text"
                           value="{{ auth()->user()->name }}"
                           readonly>
                </div>

                <div class="iv-field">
                    <label for="invoice_date">Invoice Date <b>*</b></label>
                    <input type="date" id="invoice_date"
                           name="invoice_date"
                           value="{{ old('invoice_date', date('Y-m-d')) }}"
                           required>
                </div>

                <div class="iv-field">
                    <label>Payment Status <b>*</b></label>
                    <select disabled>
                        <option>Unpaid</option>
                    </select>
                    <small>Updated automatically when payments are recorded.</small>
                </div>

                <div class="iv-field">
                    <label for="due_date">Due Date (Optional)</label>
                    <input type="date" id="due_date"
                           name="due_date"
                           value="{{ old('due_date') }}">
                </div>

                <div class="iv-field iv-full">
                    <label for="notes">Remarks (Optional)</label>
                    <textarea name="notes" id="notes" rows="2"
                              placeholder="Enter invoice remarks (optional)">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="iv-card iv-form-card">
            <div class="iv-section-row">
                <div class="iv-section-heading">
                    <h2>Invoice Items</h2>
                    <p>Add products to this invoice.</p>
                </div>

                <button type="button" id="addInvoiceItem"
                        class="iv-primary-btn">
                    ＋ Add Item
                </button>
            </div>

            <div class="iv-table-scroll">
                <table class="iv-table iv-items-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Unit Price (Rs.)</th>
                            <th>Quantity</th>
                            <th>Subtotal (Rs.)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItems"></tbody>
                </table>
            </div>

            <div class="iv-totals">
                <div>
                    <span>Subtotal (Rs.)</span>
                    <strong id="invoiceSubtotal">0.00</strong>
                </div>

                <div>
                    <label for="invoiceDiscount">Discount (Rs.)</label>
                    <input type="number"
                           id="invoiceDiscount"
                           name="discount"
                           min="0"
                           max="9999999999999.99"
                           step="0.01"
                           value="{{ old('discount', '0.00') }}"
                           style="width: 115px; height: 26px; padding: 3px 6px; border: 1px solid #d6e5fa; border-radius: 4px; text-align: right; font-size: 11px;">
                </div>

                <div>
                    <label for="invoiceTax">Tax (Rs.)</label>
                    <input type="number"
                           id="invoiceTax"
                           name="tax"
                           min="0"
                           max="9999999999999.99"
                           step="0.01"
                           value="{{ old('tax', '0.00') }}"
                           style="width: 115px; height: 26px; padding: 3px 6px; border: 1px solid #d6e5fa; border-radius: 4px; text-align: right; font-size: 11px;">
                </div>

                <div>
                    <span>Total Items</span>
                    <strong id="invoiceTotalItems">0</strong>
                </div>

                <div class="iv-grand-total">
                    <span>Total Amount (Rs.)</span>
                    <strong id="invoiceGrandTotal">0.00</strong>
                </div>
            </div>

            <div class="iv-form-actions">
                <a href="{{ route('invoices.index') }}"
                   class="iv-cancel-btn">Cancel</a>

                <button type="submit" id="saveInvoice"
                        class="iv-primary-btn">
                    ▣ Save Invoice
                </button>
            </div>
        </section>
    </form>

    <script type="application/json" id="invoiceProductsData">
{!! json_encode(
    $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'selling_price' => $product->selling_price,
            'quantity' => $product->quantity,
        ];
    })->values()->all(),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}
    </script>

    <script type="application/json" id="invoiceOldItems">
{!! json_encode(
    old('items', []),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}
    </script>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/invoice-form.js') }}?v=3" defer></script>
@endpush
