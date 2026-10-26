// Submit list filters when the search field is cleared.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        const searchInput = form.querySelector('input[type="search"][name="search"]');

        if (!searchInput) {
            return;
        }

        let resetSubmitted = false;

        const submitWhenSearchCleared = () => {
            if (searchInput.value !== '') {
                resetSubmitted = false;
                return;
            }

            if (
                !new URLSearchParams(window.location.search).get('search') ||
                resetSubmitted
            ) {
                return;
            }

            resetSubmitted = true;
            form.requestSubmit();
        };

        searchInput.addEventListener('input', submitWhenSearchCleared);
        searchInput.addEventListener('search', submitWhenSearchCleared);
    });
});
