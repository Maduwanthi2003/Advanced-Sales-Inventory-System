
// Preview selected product images and release temporary URLs.
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('productImage');
    const preview = document.getElementById('productPreview');
    const instructions = document.getElementById('uploadInstructions');

    if (!input || !preview || !instructions) {
        return;
    }

    let currentObjectUrl = null;

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png'];
        const maxSize = 2 * 1024 * 1024;

        if (!allowedTypes.includes(file.type)) {
            alert('Please select a PNG, JPG or JPEG image.');
            input.value = '';
            return;
        }

        if (file.size > maxSize) {
            alert('Image size must be 2 MB or less.');
            input.value = '';
            return;
        }

        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
        }

        currentObjectUrl = URL.createObjectURL(file);

        preview.src = currentObjectUrl;
        preview.style.display = 'block';
        instructions.style.display = 'none';
    });

    window.addEventListener('pagehide', () => {
        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
        }
    });
});
