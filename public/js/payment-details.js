
'use strict';

// Validate and confirm payment reversal submissions.
document.addEventListener('DOMContentLoaded', function () {

    const reversalForm = document.getElementById(
        'paymentReversalForm'
    );

    const reversalButton = document.getElementById(
        'reversePaymentButton'
    );

    if (!reversalForm || !reversalButton) {
        return;
    }

    let isSubmitting = false;

    reversalForm.addEventListener('submit', function (event) {

        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        const reasonInput = document.getElementById(
            'reversal_reason'
        );

        const reason = reasonInput.value.trim();

        if (reason.length < 10) {
            event.preventDefault();

            alert(
                'Please enter a reversal reason with at least 10 characters.'
            );

            reasonInput.focus();
            return;
        }

        const confirmed = window.confirm(
            'Are you sure you want to reverse this payment?\n\n' +
            'The invoice balance will be recalculated.\n' +
            'This action cannot be undone through this form.'
        );

        if (!confirmed) {
            event.preventDefault();
            return;
        }

        isSubmitting = true;
        reversalButton.disabled = true;
        reversalButton.textContent = 'Processing Reversal...';

    });

});
