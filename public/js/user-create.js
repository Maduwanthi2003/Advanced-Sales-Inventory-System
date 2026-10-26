
// Handle user form controls and profile image previews.
document.addEventListener('DOMContentLoaded', function () {
    const status = document.getElementById('is_active');
    const statusWrap = document.querySelector('.uc-status-wrap');

    function updateStatus() {
        if (!status || !statusWrap) return;

        statusWrap.classList.toggle(
            'is-inactive',
            status.value === '0'
        );
    }

    if (status) {
        status.addEventListener('change', updateStatus);
        updateStatus();
    }

    document.querySelectorAll('.uc-password-toggle').forEach(button => {
        button.addEventListener('click', function () {
            const input = document.getElementById(
                button.dataset.passwordTarget
            );

            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            button.setAttribute('aria-pressed', String(show));
            button.setAttribute(
                'aria-label',
                show ? 'Hide password' : 'Show password'
            );
        });
    });

    const input = document.getElementById('profile_image');
    const zone = document.getElementById('ucDropZone');
    const preview = document.getElementById('ucImagePreview');
    const defaultAvatar = document.getElementById('ucDefaultAvatar');

    if (!input || !zone || !preview || !defaultAvatar) return;

    let objectUrl = null;

    function showImage(file) {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        if (!file) {
            preview.hidden = true;
            preview.removeAttribute('src');
            defaultAvatar.hidden = false;
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type) || file.size > 2 * 1024 * 1024) {
            alert('Please select a JPG, PNG or WebP image under 2 MB.');
            input.value = '';
            showImage(null);
            return;
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        defaultAvatar.hidden = true;
    }

    input.addEventListener('change', function () {
        showImage(input.files[0] || null);
    });

    zone.addEventListener('dragover', function (event) {
        event.preventDefault();
        zone.classList.add('is-dragover');
    });

    zone.addEventListener('dragleave', function () {
        zone.classList.remove('is-dragover');
    });

    zone.addEventListener('drop', function (event) {
        event.preventDefault();
        zone.classList.remove('is-dragover');

        const file = event.dataTransfer.files[0];

        if (!file) return;

        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;

        showImage(file);
    });

    window.addEventListener('pagehide', function () {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
    });
});
