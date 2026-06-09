document.addEventListener('DOMContentLoaded', () => {
    const route = document.body.dataset.route;

    if (route !== 'admin_pre_order') {
        return;
    }

    const filterForm = document.querySelector('#pre-order-filter-form');

    if (!filterForm) {
        return;
    }

    const resetButton = document.querySelector('#reset-preorder-filters');

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            window.location.href = filterForm.dataset.resetUrl || '/admin/preorder';
        });
    }

    filterForm.querySelectorAll('select').forEach((field) => {
        field.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    filterForm.querySelectorAll('input[type="date"]').forEach((field) => {
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
                }, 600);
            });
        });
});
