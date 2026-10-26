document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('purchaseItems');
    const addButton = document.getElementById('addPurchaseItem');
    const form = document.getElementById('purchaseForm');

    if (!tbody || !addButton || !form) return;

    const MAX_DECIMAL_VALUE = 9999999999999.99;
    const MAX_DECIMAL_CENTS = 999999999999999;

    const products = JSON.parse(
        document.getElementById('purchaseProductsData').textContent
    );

    const oldItems = JSON.parse(
        document.getElementById('purchaseOldItems').textContent
    );

    const subtotalElement = document.getElementById('subtotal');
    const totalItemsElement = document.getElementById('totalItems');
    const grandTotalElement = document.getElementById('grandTotal');

    let nextId = 0;

    const escapeHTML = value =>
        String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[character]);

    function formatMoney(cents) {
        return (cents / 100).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function addRow(item = {}) {
        const id = nextId++;
        const tr = document.createElement('tr');

        const options = products.map(product => `
            <option value="${product.id}"
                ${String(item.product_id ?? '') === String(product.id)
                    ? 'selected' : ''}>
                ${escapeHTML(product.name)}
            </option>
        `).join('');

        tr.innerHTML = `
            <td class="pu-row-number"></td>

            <td>
                <select class="pu-product-select"
                        name="items[${id}][product_id]">
                    <option value="">Select product</option>
                    ${options}
                </select>
            </td>

            <td class="pu-item-code">—</td>

            <td>
                <input type="number"
                       class="pu-unit-cost"
                       name="items[${id}][unit_cost]"
                       min="0"
                       max="9999999999999.99"
                       step="0.01"
                       value="${escapeHTML(item.unit_cost ?? '0.00')}">
            </td>

            <td>
                <input type="number"
                       class="pu-quantity"
                       name="items[${id}][quantity]"
                       min="1"
                       max="4294967295"
                       step="1"
                       value="${escapeHTML(item.quantity ?? 1)}">
            </td>

            <td class="pu-line-total">0.00</td>

            <td>
                <button type="button"
                       class="pu-remove-item"
                       title="Remove item"
                       aria-label="Remove item">
                    <svg viewBox="0 0 24 24"
                         width="16" height="16"
                         fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M4 7h16M10 7V4h4v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/>
                    </svg>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const select = tr.querySelector('.pu-product-select');
        const cost = tr.querySelector('.pu-unit-cost');
        const quantity = tr.querySelector('.pu-quantity');

        select.addEventListener('change', () => {
            const product = products.find(
                p => String(p.id) === select.value
            );

            tr.querySelector('.pu-item-code').textContent =
                product?.sku ?? '—';

            if (product) {
                cost.value = Number(product.cost_price).toFixed(2);
            } else {
                cost.value = '0.00';
            }

            calculate();
        });

        cost.addEventListener('input', calculate);
        quantity.addEventListener('input', calculate);

        tr.querySelector('.pu-remove-item')
            .addEventListener('click', () => {
                tr.remove();
                calculate();
            });

        const selected = products.find(
            p => String(p.id) === select.value
        );

        tr.querySelector('.pu-item-code').textContent =
            selected?.sku ?? '—';

        calculate();
    }

    function calculate() {
        let totalCents = 0;
        let totalItems = 0;

        tbody.querySelectorAll('tr').forEach((tr, index) => {
            tr.querySelector('.pu-row-number').textContent = index + 1;

            const select = tr.querySelector('.pu-product-select');
            const hasProduct = Boolean(select && select.value);

            const cost = Number(
                tr.querySelector('.pu-unit-cost').value
            );

            const quantity = Number(
                tr.querySelector('.pu-quantity').value
            );

            const costCents =
                Number.isFinite(cost) && cost >= 0
                    ? Math.round(cost * 100)
                    : 0;

            const validQuantity =
                Number.isSafeInteger(quantity) && quantity > 0
                    ? quantity
                    : 0;

            const lineCents = hasProduct ? costCents * validQuantity : 0;

            tr.querySelector('.pu-line-total').textContent =
                formatMoney(lineCents);

            if (hasProduct) {
                totalCents += lineCents;
                totalItems += validQuantity;
            }
        });

        subtotalElement.textContent = formatMoney(totalCents);
        grandTotalElement.textContent = formatMoney(totalCents);
        totalItemsElement.textContent = totalItems;

        return { totalCents, totalItems };
    }

    addButton.addEventListener('click', () => addRow());

    const restoredItems = Array.isArray(oldItems)
        ? oldItems
        : Object.values(oldItems || {});

    if (restoredItems.length) {
        restoredItems.forEach(addRow);
    } else {
        addRow();
    }

    form.addEventListener('submit', event => {
        const rows = [...tbody.querySelectorAll('tr')];
        const activeRows = rows.filter(row =>
            Boolean(row.querySelector('.pu-product-select')?.value)
        );

        if (!activeRows.length) {
            event.preventDefault();
            alert('Please select at least one product.');
            return;
        }

        const selected = activeRows.map(row =>
            row.querySelector('.pu-product-select').value
        );

        if (new Set(selected).size !== selected.length) {
            event.preventDefault();
            alert('Please select different products for each item.');
            return;
        }

        let runningTotalCents = 0;

        for (const row of activeRows) {
            const cost = Number(
                row.querySelector('.pu-unit-cost').value
            );

            const quantity = Number(
                row.querySelector('.pu-quantity').value
            );

            if (!Number.isFinite(cost) || cost < 0 || cost > MAX_DECIMAL_VALUE ||
                !Number.isSafeInteger(quantity) || quantity < 1 || quantity > 4294967295) {
                event.preventDefault();
                alert('Please enter valid unit costs and quantities within allowed limits.');
                return;
            }

            const lineCents = Math.round(cost * 100) * quantity;

            if (lineCents > MAX_DECIMAL_CENTS) {
                event.preventDefault();
                alert('A line item total exceeds the maximum allowed amount (Rs. 9,999,999,999,999.99).');
                return;
            }

            runningTotalCents += lineCents;
        }

        if (runningTotalCents > MAX_DECIMAL_CENTS) {
            event.preventDefault();
            alert('Purchase total exceeds the maximum allowed amount (Rs. 9,999,999,999,999.99).');
            return;
        }

        // Remove unused empty rows only after validation succeeds.
        rows.forEach(row => {
            const select = row.querySelector('.pu-product-select');
            if (!select || !select.value) {
                row.remove();
            }
        });

        calculate();

        const saveButton = document.getElementById('savePurchase');
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    });
});
