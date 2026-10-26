{{-- Shared confirmation dialog used by delete forms. --}}
<dialog
    class="delete-confirmation-dialog"
    id="deleteConfirmationDialog"
    aria-labelledby="deleteConfirmationTitle"
    aria-describedby="deleteConfirmationMessage"
>
    <div class="delete-confirmation-content">
        <h2 id="deleteConfirmationTitle">Delete record?</h2>
        <p id="deleteConfirmationMessage">
            Are you sure you want to delete this record? This action cannot be undone.
        </p>

        <div class="delete-confirmation-actions">
            <button type="button" class="delete-confirmation-cancel" data-delete-cancel>
                Cancel
            </button>
            <button type="button" class="delete-confirmation-confirm" data-delete-confirm>
                Delete
            </button>
        </div>
    </div>
</dialog>
