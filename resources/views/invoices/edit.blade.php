{{-- Invoice details and line-item editing form. --}}
@extends('layouts.app')

@section('content')
<div class="iv-edit-page">

    <div class="iv-edit-header">
        <div>
            <p><strong>{{ $invoice->invoice_number }}</strong></p>
        </div>

        <a href="{{ route('invoices.show', $invoice) }}"
           class="iv-btn iv-btn-light">
            Back to Invoice
        </a>
    </div>

    @if ($errors->any())
        <div class="iv-alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('invoices.update', $invoice) }}"
          id="invoiceEditForm">

        @csrf
        @method('PUT')

        <div class="iv-panel">
            <h2>Invoice Information</h2>

            <div class="iv-form-grid">
                <div class="iv-field">
                    <label>Customer *</label>
                    <select name="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected(
                                    old('customer_id', $invoice->customer_id)
                                    == $customer->id
                                )>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="iv-field">
                    <label>Invoice Date *</label>
                    <input type="date"
                           name="invoice_date"
                           value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}"
                           required>
                </div>

                <div class="iv-field">
                    <label>Due Date</label>
                    <input type="date"
                           name="due_date"
                           value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                </div>
            </div>
        </div>

        <div class="iv-panel">
            <div class="iv-section-heading">
                <h2>Invoice Items</h2>

                <button type="button"
                        class="iv-btn iv-btn-primary"
                        id="addInvoiceItem">
                    + Add Item
                </button>
            </div>

            <div class="iv-table-scroll">
                <table class="iv-edit-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Available Stock</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="invoiceItemsBody"></tbody>
                </table>
            </div>

            <div class="iv-form-grid" style="margin-top: 18px;">
                <div class="iv-field">
                    <label>Subtotal (Rs.)</label>
                    <input type="text" id="invoiceSubtotal" value="Rs. 0.00" readonly>
                </div>

                <div class="iv-field">
                    <label for="invoiceDiscount">Discount (Rs.)</label>
                    <input type="number"
                           id="invoiceDiscount"
                           name="discount"
                           min="0"
                           max="9999999999999.99"
                           step="0.01"
                           value="{{ old('discount', number_format((float) $invoice->discount, 2, '.', '')) }}">
                </div>

                <div class="iv-field">
                    <label for="invoiceTax">Tax (Rs.)</label>
                    <input type="number"
                           id="invoiceTax"
                           name="tax"
                           min="0"
                           max="9999999999999.99"
                           step="0.01"
                           value="{{ old('tax', number_format((float) $invoice->tax, 2, '.', '')) }}">
                </div>
            </div>

            <div class="iv-total">
                <span>Grand Total</span>
                <strong id="invoiceGrandTotal">Rs. 0.00</strong>
            </div>
        </div>

        <div class="iv-panel">
            <div class="iv-field">
                <label>Notes</label>
                <textarea name="notes" rows="4">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </div>

        <div class="iv-actions">
            <a href="{{ route('invoices.show', $invoice) }}"
               class="iv-btn iv-btn-light">
                Cancel Changes
            </a>

            <button type="submit" class="iv-btn iv-btn-primary">
                Update Invoice
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/invoice-edit.css') }}">
@endpush

@push('scripts')
<script>
window.invoiceEditData = {
    products: {!! json_encode(
        $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => (int) $product->quantity,
            'selling_price' => $product->selling_price,
        ])->values()
    ) !!},

    originalQuantities: {!! json_encode(
        $invoice->items->pluck('quantity', 'product_id')
    ) !!},

    originalPrices: {!! json_encode(
        $invoice->items->pluck('unit_price', 'product_id')
    ) !!},

    items: {!! json_encode(
        old('items', $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->values()->all())
    ) !!}
};
</script>
<script src="{{ asset('js/invoice-edit.js') }}?v=2" defer></script>
@endpush
