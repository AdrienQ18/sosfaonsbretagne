document.addEventListener('DOMContentLoaded', () => {

    const route = document.body.dataset.route;

    if (route !== 'shop') {
        return;
    }

    /* =====================
       LIGHTBOX IMAGES
    ===================== */

    const viewer = document.getElementById('image-viewer');
    const viewerImg = document.getElementById('image-viewer-img');
    const closeBtn = document.querySelector('.image-viewer-close');

    document.querySelectorAll('.js-image-viewer').forEach((image) => {

        image.addEventListener('click', () => {

            viewerImg.src = image.src;
            viewerImg.alt = image.alt;

            viewer.classList.add('active');

        });

    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            viewer.classList.remove('active');
        });
    }

    if (viewer) {
        viewer.addEventListener('click', (event) => {

            if (event.target === viewer) {
                viewer.classList.remove('active');
            }

        });
    }

    /* =====================
       MODALS PRODUITS
    ===================== */

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

        });

    });

    document.querySelectorAll('.boutique-modal').forEach((modal) => {

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

    function closeModal(modal) {

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');

    }

});
