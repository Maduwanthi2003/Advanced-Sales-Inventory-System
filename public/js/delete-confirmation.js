// Set up confirmation handling for delete forms.
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const dialog = document.getElementById('deleteConfirmationDialog');
    const title = document.getElementById('deleteConfirmationTitle');
    const message = document.getElementById('deleteConfirmationMessage');
    const cancelButton = dialog?.querySelector('[data-delete-cancel]');
    const confirmButton = dialog?.querySelector('[data-delete-confirm]');

    if (!(dialog instanceof HTMLDialogElement)
        || !(title instanceof HTMLElement)
        || !(message instanceof HTMLElement)
        || !(cancelButton instanceof HTMLButtonElement)
        || !(confirmButton instanceof HTMLButtonElement)) {
        throw new Error('Delete confirmation dialog markup is incomplete.');
    }

    let pendingForm = null;
    let pendingSubmitter = null;
    let returnFocusTo = null;
    const approvedForms = new WeakSet();

    function cancelPendingConfirmation() {
        pendingForm = null;
        pendingSubmitter = null;
        dialog.close('cancel');
    }

    document.addEventListener('submit', event => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-delete')) {
            return;
        }

        if (approvedForms.has(form)) {
            approvedForms.delete(form);
            return;
        }

        event.preventDefault();

        if (pendingForm && dialog.open) {
            return;
        }

        pendingForm = null;
        pendingSubmitter = null;

        const type = form.dataset.deleteType || 'record';
        const name = form.dataset.deleteName || '';

        title.textContent = `Delete ${type}?`;
        message.textContent = name
            ? `Are you sure you want to delete ${name}? This action cannot be undone.`
            : `Are you sure you want to delete this ${type}? This action cannot be undone.`;

        pendingForm = form;
        pendingSubmitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        returnFocusTo = pendingSubmitter || form.querySelector('button[type="submit"]');

        dialog.returnValue = '';
        dialog.showModal();
        cancelButton.focus();
    });

    cancelButton.addEventListener('click', () => {
        cancelPendingConfirmation();
    });

    confirmButton.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        confirmButton.disabled = true;
        approvedForms.add(form);
        dialog.close('confirm');

        if (submitter instanceof HTMLButtonElement
            && submitter.form === form) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }
    });

    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        cancelPendingConfirmation();
    });

    dialog.addEventListener('close', () => {
        confirmButton.disabled = false;

        const wasCancelled = dialog.returnValue !== 'confirm';
        pendingForm = null;
        pendingSubmitter = null;

        if (wasCancelled && returnFocusTo instanceof HTMLElement && returnFocusTo.isConnected) {
            returnFocusTo.focus();
        }

        returnFocusTo = null;
    });
});
