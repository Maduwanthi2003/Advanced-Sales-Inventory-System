// Update the displayed state and submitted value when the toggle is clicked.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-status-toggle]');

    if (!button || button.disabled) {
        return;
    }

    const control = button.closest('.status-toggle-control');
    const valueInput = control?.querySelector('[data-status-value]');
    const label = button.querySelector('[data-status-label]');

    if (!valueInput || !label) {
        return;
    }

    const isActive = valueInput.value !== button.dataset.activeValue;

    valueInput.value = isActive
        ? button.dataset.activeValue
        : button.dataset.inactiveValue;
    button.setAttribute('aria-checked', String(isActive));
    button.classList.toggle('is-active', isActive);
    button.classList.toggle('is-inactive', !isActive);
    label.textContent = isActive ? 'Active' : 'Inactive';
});
