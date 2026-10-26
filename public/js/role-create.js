
// Manage role permissions, status display, and description count.
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const form =
    document.getElementById('createRoleForm') ||
    document.getElementById('editRoleForm');
    const selectAll = document.getElementById('selectAllPermissions');
    const statusSelect = document.getElementById('roleStatus');
    const statusGroup = document.querySelector('.create-role-status-group');
    const description = document.getElementById('roleDescription');
    const descriptionCount = document.getElementById('descriptionCount');
    const saveButton = document.getElementById('saveRoleButton');

    const permissionCheckboxes = [
        ...document.querySelectorAll('.create-role-permission-checkbox')
    ];

    function updateSelectAll() {
        if (!selectAll) return;

        const selected = permissionCheckboxes.filter(box => box.checked).length;

        selectAll.checked =
            permissionCheckboxes.length > 0 &&
            selected === permissionCheckboxes.length;

        selectAll.indeterminate =
            selected > 0 && selected < permissionCheckboxes.length;
    }

    selectAll?.addEventListener('change', () => {
        permissionCheckboxes.forEach(box => {
            box.checked = selectAll.checked;
        });

        updateSelectAll();
    });

    permissionCheckboxes.forEach(box => {
        box.addEventListener('change', updateSelectAll);
    });

    function updateStatusAppearance() {
        if (!statusGroup || !statusSelect) return;

        statusGroup.classList.toggle(
            'inactive',
            statusSelect.value === '0'
        );
    }

    statusSelect?.addEventListener('change', updateStatusAppearance);

    function updateDescriptionCount() {
        if (!description || !descriptionCount) return;

        descriptionCount.textContent = description.value.length;
    }

    description?.addEventListener('input', updateDescriptionCount);

    form?.addEventListener('submit', () => {
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
        }
    });

    updateSelectAll();
    updateStatusAppearance();
    updateDescriptionCount();
});
