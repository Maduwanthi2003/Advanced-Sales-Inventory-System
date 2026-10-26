
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const form = document.getElementById('editUserForm');

    if (!form) return;

    // Password visibility
    document.querySelectorAll('.uc-password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(
                button.dataset.passwordTarget
            );

            if (!input) return;

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(isHidden));
            button.setAttribute(
                'aria-label',
                isHidden ? 'Hide password' : 'Show password'
            );
        });
    });

    // Status indicator
    const statusSelect = document.getElementById('is_active');
    const statusWrap = document.querySelector('.uc-status-wrap');

    const updateStatus = () => {
        if (!statusSelect || !statusWrap) return;

        statusWrap.classList.toggle(
            'uc-status-inactive',
            statusSelect.value === '0'
        );
    };

    statusSelect?.addEventListener('change', updateStatus);
    updateStatus();

    // Profile image
    const fileInput = document.getElementById('profile_image');
    const dropZone = document.getElementById('ucDropZone');
    const imagePreview = document.getElementById('ucImagePreview');
    const defaultAvatar = document.getElementById('ucDefaultAvatar');

    if (!fileInput || !dropZone || !imagePreview) return;

    const maxSize = 2 * 1024 * 1024;
    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    let objectUrl = null;

    const showImage = file => {
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a JPG, PNG or WebP image.');
            fileInput.value = '';
            return;
        }

        if (file.size > maxSize) {
            alert('Profile image must not exceed 2MB.');
            fileInput.value = '';
            return;
        }

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = URL.createObjectURL(file);

        imagePreview.src = objectUrl;
        imagePreview.hidden = false;

        if (defaultAvatar) {
            defaultAvatar.hidden = true;
        }
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];

        if (file) showImage(file);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, event => {
            event.preventDefault();
            dropZone.classList.add('uc-drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, event => {
            event.preventDefault();
            dropZone.classList.remove('uc-drag-over');
        });
    });

    dropZone.addEventListener('drop', event => {
        const file = event.dataTransfer?.files?.[0];

        if (!file) return;

        if (
            !allowedTypes.includes(file.type) ||
            file.size > maxSize
        ) {
            showImage(file);
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);

        fileInput.files = transfer.files;

        showImage(file);
    });

    window.addEventListener('pagehide', () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
    });
});
