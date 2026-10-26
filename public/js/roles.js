
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const searchInput = document.getElementById('roleSearch');
    const roleSelect = document.getElementById('selectedRole');
    const selectAll = document.getElementById('rolesSelectAll');
    const saveButton = document.getElementById('rolesSavePermissions');

    const permissionsForm = document.getElementById('rolePermissionsForm');
    const permissionInputs = document.getElementById('selectedPermissionInputs');

    const permissionCheckboxes = [
        ...document.querySelectorAll('.roles-permission-checkbox')
    ];

    const permissionDataElement =
        document.getElementById('rolesPermissionData');

    let rolePermissions = {};

    try {
        rolePermissions = JSON.parse(
            permissionDataElement?.textContent || '{}'
        );
    } catch (error) {
        console.error('Unable to load role permissions.', error);
    }

    // Search Roles
    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();

        document.querySelectorAll('.role-data-row').forEach(row => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    });

    // Update Select All State
    function syncSelectAll() {
        if (!selectAll) return;

        const checkedCount =
            permissionCheckboxes.filter(box => box.checked).length;

        selectAll.checked =
            permissionCheckboxes.length > 0 &&
            checkedCount === permissionCheckboxes.length;

        selectAll.indeterminate =
            checkedCount > 0 &&
            checkedCount < permissionCheckboxes.length;
    }

    // Load Permissions For Selected Role
    function loadRolePermissions(roleId) {
        const hasRole = Boolean(roleId);

        const assigned = new Set(
            (rolePermissions[roleId] || []).map(String)
        );

        const roleName = hasRole
            ? roleSelect.options[roleSelect.selectedIndex].text.trim()
            : '';

        const isAdmin = roleName.toLowerCase() === 'admin';

        permissionCheckboxes.forEach(box => {
            box.checked = hasRole && assigned.has(box.value);
            box.disabled = !hasRole || isAdmin;
        });

        if (selectAll) {
            selectAll.disabled = !hasRole || isAdmin;
        }

        if (saveButton) {
            saveButton.disabled = true;
        }

        syncSelectAll();
    }

    // Change Role
    roleSelect?.addEventListener('change', () => {
        loadRolePermissions(roleSelect.value);
    });

    // Select All Permissions
    selectAll?.addEventListener('change', () => {
        permissionCheckboxes.forEach(box => {
            if (!box.disabled) {
                box.checked = selectAll.checked;
            }
        });

        if (saveButton) {
            saveButton.disabled = !roleSelect.value;
        }

        syncSelectAll();
    });

    // Individual Permission Checkbox
    permissionCheckboxes.forEach(box => {
        box.addEventListener('change', () => {
            if (saveButton) {
                saveButton.disabled = !roleSelect.value;
            }

            syncSelectAll();
        });
    });

    // View Role Permissions
    document.querySelectorAll('[data-view-role]').forEach(button => {
        button.addEventListener('click', () => {
            if (!roleSelect) return;

            roleSelect.value = button.dataset.viewRole;
            loadRolePermissions(roleSelect.value);

            document.querySelector('.roles-permissions-panel')
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
        });
    });

    // Save Permissions
    saveButton?.addEventListener('click', () => {
        const roleId = roleSelect?.value;

        if (!roleId || !permissionsForm || !permissionInputs) {
            return;
        }

        const roleName =
            roleSelect.options[roleSelect.selectedIndex].text.trim();

        if (roleName.toLowerCase() === 'admin') {
            alert('Admin role permissions cannot be modified.');
            return;
        }

        if (!window.confirm(`Save permissions for "${roleName}"?`)) {
            return;
        }

        permissionsForm.action =
            `/roles/${encodeURIComponent(roleId)}/permissions`;

        permissionInputs.replaceChildren();

        permissionCheckboxes
            .filter(box => box.checked)
            .forEach(box => {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = 'permissions[]';
                input.value = box.value;

                permissionInputs.appendChild(input);
            });

        permissionsForm.submit();
    });

    // Initial State
    loadRolePermissions('');
});
