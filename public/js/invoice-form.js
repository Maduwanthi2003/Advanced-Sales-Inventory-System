document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('invoiceItems');
    const addButton = document.getElementById('addInvoiceItem');
    const form = document.getElementById('invoiceForm');

    if (!tbody || !addButton || !form) return;

    const MAX_DECIMAL_CENTS = 999999999999999;

    const products = JSON.parse(
        document.getElementById('invoiceProductsData').textContent
    );

    const oldItems = JSON.parse(
        document.getElementById('invoiceOldItems').textContent
    );

    const subtotalEl = document.getElementById('invoiceSubtotal');
    const discountInput = document.getElementById('invoiceDiscount');
    const taxInput = document.getElementById('invoiceTax');
    const totalItemsEl = document.getElementById('invoiceTotalItems');
    const grandTotalEl = document.getElementById('invoiceGrandTotal');

    let nextIndex = 0;

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[character]);
    }

    function money(cents) {
        return (cents / 100).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function toCents(inputEl) {
        if (!inputEl) return 0;
        const val = Number(inputEl.value);
        if (!Number.isFinite(val) || val < 0) return 0;
        return Math.round(val * 100);
    }

    function addRow(item = {}) {
        const index = nextIndex++;
        const row = document.createElement('tr');

        const options = products.map(product => `
            <option value="${product.id}"
                ${String(item.product_id ?? '') === String(product.id)
                    ? 'selected' : ''}>
                ${escapeHTML(product.name)}
            </option>
        `).join('');

        row.innerHTML = `
            <td class="iv-row-number"></td>

            <td>
                <select class="iv-product-select"
                        name="items[${index}][product_id]">
                    <option value="">Select product</option>
                    ${options}
                </select>
            </td>

            <td class="iv-product-code">—</td>

            <td>
                <input class="iv-unit-price"
                       type="number"
                       min="0"
                       max="9999999999999.99"
                       step="0.01"
                       value="0.00"
                       readonly>
            </td>

            <td>
                <input class="iv-quantity"
                       type="number"
                       name="items[${index}][quantity]"
                       min="1"
                       max="4294967295"
                       step="1"
                       value="${escapeHTML(item.quantity ?? 1)}">
            </td>

            <td class="iv-item-subtotal">0.00</td>

            <td>
                <button type="button"
                        class="iv-remove-item"
                        title="Remove item"
                        aria-label="Remove item">
                    🗑
                </button>
            </td>
        `;

        tbody.appendChild(row);

        const select = row.querySelector('.iv-product-select');
        const quantityInput = row.querySelector('.iv-quantity');

        function updateProduct() {
            const product = products.find(
                p => String(p.id) === select.value
            );

            row.querySelector('.iv-product-code').textContent =
                product?.sku ?? '—';

            row.querySelector('.iv-unit-price').value = product
                ? Number(product.selling_price).toFixed(2)
                : '0.00';

            calculate();
        }

        select.addEventListener('change', updateProduct);
        quantityInput.addEventListener('input', calculate);

        row.querySelector('.iv-remove-item')
            .addEventListener('click', () => {
                row.remove();
                calculate();
            });

        updateProduct();
    }

    function calculate() {
        let subtotalCents = 0;
        let totalItems = 0;

        tbody.querySelectorAll('tr').forEach((row, index) => {
            row.querySelector('.iv-row-number').textContent = index + 1;

            const product = products.find(
                p => String(p.id) ===
                    row.querySelector('.iv-product-select').value
            );

            const quantity = Number(
                row.querySelector('.iv-quantity').value
            );

            const validQuantity =
                Number.isSafeInteger(quantity) && quantity > 0
                    ? quantity
                    : 0;

            const priceCents = product
                ? Math.round(Number(product.selling_price) * 100)
                : 0;

            const lineCents = priceCents * validQuantity;

            row.querySelector('.iv-item-subtotal').textContent =
                money(lineCents);

            if (product) {
                subtotalCents += lineCents;
                totalItems += validQuantity;
            }
        });

        const discountCents = toCents(discountInput);
        const taxCents = toCents(taxInput);
        const grandTotalCents = Math.max(0, subtotalCents - discountCents + taxCents);

        subtotalEl.textContent = money(subtotalCents);
        grandTotalEl.textContent = money(grandTotalCents);
        totalItemsEl.textContent = totalItems;

        return { subtotalCents, discountCents, taxCents, grandTotalCents };
    }

    if (discountInput) {
        discountInput.addEventListener('input', calculate);
    }

    if (taxInput) {
        taxInput.addEventListener('input', calculate);
    }

    addButton.addEventListener('click', () => addRow());

    const restored = Array.isArray(oldItems)
        ? oldItems
        : Object.values(oldItems || {});

    if (restored.length) {
        restored.forEach(addRow);
    } else {
        addRow();
    }

    form.addEventListener('submit', event => {
        const rows = [...tbody.querySelectorAll('tr')];
        const activeRows = rows.filter(row =>
            Boolean(row.querySelector('.iv-product-select')?.value)
        );

        if (!activeRows.length) {
            event.preventDefault();
            if (!rows.length) {
                addRow();
            }
            alert('Please select at least one product.');
            return;
        }

        const selectedIds = activeRows.map(row =>
            row.querySelector('.iv-product-select').value
        );

        if (new Set(selectedIds).size !== selectedIds.length) {
            event.preventDefault();
            alert('Each product can only be selected once.');
            return;
        }

        for (const row of activeRows) {
            const quantity = Number(
                row.querySelector('.iv-quantity').value
            );

            const product = products.find(
                p => String(p.id) ===
                    row.querySelector('.iv-product-select').value
            );

            if (!Number.isSafeInteger(quantity) || quantity < 1 || quantity > 4294967295) {
                event.preventDefault();
                alert('Please enter a valid quantity.');
                return;
            }

            if (product && quantity > Number(product.quantity)) {
                event.preventDefault();
                alert(
                    `Insufficient stock for ${product.name}. ` +
                    `Available: ${product.quantity}`
                );
                return;
            }

            const lineCents = product
                ? Math.round(Number(product.selling_price) * 100) * quantity
                : 0;

            if (lineCents > MAX_DECIMAL_CENTS) {
                event.preventDefault();
                alert('A line item total exceeds the maximum allowed amount (Rs. 9,999,999,999,999.99).');
                return;
            }
        }

        const totals = calculate();

        if (totals.discountCents > totals.subtotalCents) {
            event.preventDefault();
            alert('Discount cannot exceed the invoice subtotal.');
            return;
        }

        if (totals.subtotalCents > MAX_DECIMAL_CENTS || totals.grandTotalCents > MAX_DECIMAL_CENTS) {
            event.preventDefault();
            alert('Invoice total exceeds the maximum allowed amount (Rs. 9,999,999,999,999.99).');
            return;
        }

        // Remove unused empty placeholder rows right before submission.
        rows.forEach(row => {
            const select = row.querySelector('.iv-product-select');
            if (!select || !select.value) {
                row.remove();
            }
        });

        calculate();

        const saveButton = document.getElementById('saveInvoice');
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    });
});
