import {isRoute, onReady} from './dom.js';
import {initImageLightbox} from './lightbox.js';

onReady(() => {
    if (!isRoute('shop')) {
        return;
    }

    /* =====================
       LIGHTBOX IMAGES
    ===================== */

    initImageLightbox({
        triggerSelector: '.js-image-viewer',
        lightboxSelector: '#image-viewer',
        imageSelector: '#image-viewer-img',
        closeSelector: '.image-viewer-close',
    });

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
