document.addEventListener('DOMContentLoaded', () => {
    const route = document.body.dataset.route;

    if (route !== 'shop') {
        return;
    }

    const openButtons = document.querySelectorAll('.boutique-info-button');

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.dataset.modal;
            const modal = document.getElementById(modalId);

            if (!modal) {
                return;
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');

            const closeButton = modal.querySelector('.boutique-modal-close');

            if (closeButton) {
                closeButton.addEventListener('click', () => {
                    closeModal(modal);
                });
            }

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });
    });

    function closeModal(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }
});
