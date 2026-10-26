
// Update invoice summaries and validate payment form submissions.
document.addEventListener('DOMContentLoaded', () => {
    const invoices = window.paymentInvoiceData || [];

    const form = document.getElementById('paymentForm');
    const invoiceSelect = document.getElementById('invoice_id');
    const amountInput = document.getElementById('payment_amount');
    const saveButton = document.getElementById('savePaymentButton');
    const message = document.getElementById('paymentBalanceMessage');

    if (!form || !invoiceSelect || !amountInput) return;

    const byId = id => document.getElementById(id);

    const formatMoney = amount =>
        Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    let currentBalanceCents = 0;

    function updateSummary() {
        const invoice = invoices.find(
            item => String(item.id) === invoiceSelect.value
        );

        const totalCents = invoice
            ? Math.round(Number(invoice.total) * 100)
            : 0;

        const paidCents = invoice
            ? Math.round(Number(invoice.paid) * 100)
            : 0;

        currentBalanceCents = Math.max(
            0,
            totalCents - paidCents
        );

        byId('paymentCustomer').value = invoice?.customer || '';
        byId('paymentInvoiceDate').value =
            invoice?.invoice_date || '';

        byId('summaryInvoice').textContent =
            invoice?.invoice_number || '—';

        byId('summaryCustomer').textContent =
            invoice?.customer || '—';

        byId('summaryDate').textContent =
            invoice?.invoice_date || '—';

        byId('summaryTotal').textContent =
            formatMoney(totalCents / 100);

        byId('summaryPaid').textContent =
            formatMoney(paidCents / 100);

        byId('summaryBalance').textContent =
            formatMoney(currentBalanceCents / 100);

        amountInput.max = invoice
            ? (currentBalanceCents / 100).toFixed(2)
            : '';

        validateAmount();
    }

    function validateAmount() {
        const amountCents = Math.round(
            Number(amountInput.value || 0) * 100
        );

        byId('summaryPayment').textContent =
            formatMoney(amountCents / 100);

        const invoiceSelected = Boolean(invoiceSelect.value);

        if (!invoiceSelected) {
            message.textContent = 'Please select an invoice.';
            message.className = 'pay-balance-message';
            saveButton.disabled = true;
            return false;
        }

        if (amountCents <= 0) {
            message.textContent =
                'Enter a payment amount greater than zero.';
            message.className = 'pay-balance-message';
            saveButton.disabled = true;
            return false;
        }

        if (amountCents > currentBalanceCents) {
            message.textContent =
                'Payment cannot exceed the outstanding balance.';
            message.className =
                'pay-balance-message pay-message-error';
            saveButton.disabled = true;
            return false;
        }

        const remaining = currentBalanceCents - amountCents;

        message.textContent = remaining === 0
            ? 'This payment will fully settle the invoice.'
            : 'Remaining balance after payment: Rs. '
                + formatMoney(remaining / 100);

        message.className =
            'pay-balance-message pay-message-success';

        saveButton.disabled = false;
        return true;
    }

    invoiceSelect.addEventListener('change', updateSummary);
    amountInput.addEventListener('input', validateAmount);

    form.addEventListener('submit', event => {
        if (!validateAmount()) {
            event.preventDefault();
        }
    });

    updateSummary();
});
