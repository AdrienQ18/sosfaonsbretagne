export function initAutoSubmitFilterForm({
    formSelector,
    resetSelector,
    fallbackResetUrl = null,
    debounceDelay = 600,
}) {
    const filterForm = document.querySelector(formSelector);

    if (!filterForm) {
        return;
    }

    const resetButton = document.querySelector(resetSelector);

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            const resetUrl = filterForm.dataset.resetUrl || fallbackResetUrl;

            if (resetUrl) {
                window.location.href = resetUrl;
            }
        });
    }

    filterForm.querySelectorAll('select, input[type="date"]').forEach((field) => {
        field.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    let timeout;

    filterForm
        .querySelectorAll('input[type="text"], input[type="search"], input[type="email"]')
        .forEach((field) => {
            field.addEventListener('input', () => {
                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    filterForm.submit();
                }, debounceDelay);
            });
        });
}
