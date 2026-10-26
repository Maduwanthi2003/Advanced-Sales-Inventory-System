document.addEventListener('DOMContentLoaded', () => {
    const data = window.invoiceEditData || {};
    const products = data.products || [];
    const originalQuantities = data.originalQuantities || {};
    const originalPrices = data.originalPrices || {};
    const initialItems = data.items || [];

    const MAX_DECIMAL_CENTS = 999999999999999;

    const tbody = document.getElementById('invoiceItemsBody');
    const addButton = document.getElementById('addInvoiceItem');
    const subtotalElement = document.getElementById('invoiceSubtotal');
    const discountInput = document.getElementById('invoiceDiscount');
    const taxInput = document.getElementById('invoiceTax');
    const totalElement = document.getElementById('invoiceGrandTotal');
    const form = document.getElementById('invoiceEditForm');

    if (!tbody || !form) return;

    let nextIndex = 0;

    const moneyFromCents = cents =>
        'Rs. ' + (Number(cents || 0) / 100).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    function toCents(inputEl) {
        if (!inputEl) return 0;
        const val = Number(inputEl.value);
        if (!Number.isFinite(val) || val < 0) return 0;
        return Math.round(val * 100);
    }

    function getEffectiveStock(productId) {
        const selected = products.find(
            product => String(product.id) === String(productId)
        );
        if (!selected) return null;
        const warehouseQty = Number(selected.quantity) || 0;
        const heldByInvoiceQty = Number(originalQuantities[selected.id] || 0);
        return warehouseQty + heldByInvoiceQty;
    }

    function getUnitPrice(productId, fallbackPrice) {
        if (originalPrices[productId] !== undefined) {
            return Number(originalPrices[productId]);
        }
        if (fallbackPrice !== undefined) {
            return Number(fallbackPrice);
        }
        const selected = products.find(
            product => String(product.id) === String(productId)
        );
        return Number(selected?.selling_price || 0);
    }

    function addRow(item = {}) {
        const index = nextIndex++;
        const row = document.createElement('tr');

        const productCell = document.createElement('td');
        const stockCell = document.createElement('td');
        const quantityCell = document.createElement('td');
        const priceCell = document.createElement('td');
        const totalCell = document.createElement('td');
        const actionCell = document.createElement('td');

        const select = document.createElement('select');
        select.name = `items[${index}][product_id]`;
        select.required = true;

        const placeholder = new Option('Select Product', '');
        select.add(placeholder);

        products.forEach(product => {
            const option = new Option(product.name, product.id);
            select.add(option);
        });

        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.name = `items[${index}][quantity]`;
        quantity.min = '1';
        quantity.max = '4294967295';
        quantity.step = '1';
        quantity.required = true;
        quantity.value = item.quantity || 1;

        const stock = document.createElement('span');
        const price = document.createElement('span');
        const lineTotal = document.createElement('strong');

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'iv-remove';
        remove.textContent = 'Remove';

        const initialProductId = String(item.product_id || '');

        function refreshRow() {
            const selected = products.find(
                product => String(product.id) === select.value
            );

            const qty = Math.max(0, Number(quantity.value) || 0);

            // Existing invoice lines retain their original price.
            const unitPrice = selected
                ? getUnitPrice(selected.id, select.value === initialProductId ? item.unit_price : undefined)
                : 0;

            const priceCents = Math.round(unitPrice * 100);
            const lineCents = priceCents * qty;
            const effectiveStock = selected ? getEffectiveStock(selected.id) : null;

            stock.textContent = effectiveStock !== null
                ? effectiveStock
                : '—';

            price.textContent = moneyFromCents(priceCents);
            lineTotal.textContent = moneyFromCents(lineCents);

            row.dataset.lineCents = String(lineCents);
            calculateTotal();
        }

        select.value = initialProductId;
        select.addEventListener('change', refreshRow);
        quantity.addEventListener('input', refreshRow);

        remove.addEventListener('click', () => {
            row.remove();
            calculateTotal();
        });

        productCell.appendChild(select);
        stockCell.appendChild(stock);
        quantityCell.appendChild(quantity);
        priceCell.appendChild(price);
        totalCell.appendChild(lineTotal);
        actionCell.appendChild(remove);

        row.append(
            productCell,
            stockCell,
            quantityCell,
            priceCell,
            totalCell,
            actionCell
        );

        tbody.appendChild(row);
        refreshRow();
    }

    function calculateTotal() {
        const subtotalCents = Array.from(tbody.querySelectorAll('tr'))
            .reduce((sum, row) => {
                return sum + Number(row.dataset.lineCents || 0);
            }, 0);

        const discountCents = toCents(discountInput);
        const taxCents = toCents(taxInput);
        const grandTotalCents = Math.max(0, subtotalCents - discountCents + taxCents);

        if (subtotalElement) {
            subtotalElement.value = moneyFromCents(subtotalCents);
        }

        totalElement.textContent = moneyFromCents(grandTotalCents);

        return { subtotalCents, discountCents, taxCents, grandTotalCents };
    }

    if (discountInput) {
        discountInput.addEventListener('input', calculateTotal);
    }

    if (taxInput) {
        taxInput.addEventListener('input', calculateTotal);
    }

    addButton.addEventListener('click', () => addRow());

    form.addEventListener('submit', event => {
        const rows = Array.from(tbody.querySelectorAll('tr'));

        if (rows.length === 0) {
            event.preventDefault();
            alert('Please add at least one product.');
            return;
        }

        const selectedIds = rows.map(
            row => row.querySelector('select').value
        );

        if (selectedIds.some(id => !id)) {
            event.preventDefault();
            alert('Please select a product in every row.');
            return;
        }

        if (new Set(selectedIds).size !== selectedIds.length) {
            event.preventDefault();
            alert('The same product cannot be selected twice.');
            return;
        }

        for (const row of rows) {
            const productId = row.querySelector('select').value;
            const qty = Number(row.querySelector('input[type="number"]').value);
            const product = products.find(p => String(p.id) === String(productId));
            const availableStock = getEffectiveStock(productId);
            const lineCents = Number(row.dataset.lineCents || 0);

            if (!Number.isSafeInteger(qty) || qty < 1 || qty > 4294967295) {
                event.preventDefault();
                alert('Please enter a valid quantity.');
                return;
            }

            if (availableStock !== null && qty > availableStock) {
                event.preventDefault();
                alert(
                    `Insufficient stock for ${product?.name || 'selected product'}. ` +
                    `Available: ${availableStock}`
                );
                return;
            }

            if (lineCents > MAX_DECIMAL_CENTS) {
                event.preventDefault();
                alert('A line item total exceeds the maximum allowed amount (Rs. 9,999,999,999,999.99).');
                return;
            }
        }

        const totals = calculateTotal();

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
    });

    const restored = Array.isArray(initialItems)
        ? initialItems
        : Object.values(initialItems);

    if (restored.length) {
        restored.forEach(addRow);
    } else {
        addRow();
    }
});
