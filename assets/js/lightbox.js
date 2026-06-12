export function initImageLightbox({
    triggerSelector,
    lightboxSelector,
    imageSelector,
    closeSelector,
    activeClass = 'active',
}) {
    const lightbox = document.querySelector(lightboxSelector);
    const lightboxImage = document.querySelector(imageSelector);

    if (!lightbox || !lightboxImage) {
        return;
    }

    document.querySelectorAll(triggerSelector).forEach((trigger) => {
        trigger.addEventListener('click', () => {
            lightboxImage.src = trigger.src;
            lightboxImage.alt = trigger.alt || '';
            lightbox.classList.add(activeClass);
            lightbox.setAttribute('aria-hidden', 'false');
        });
    });

    const close = () => {
        lightbox.classList.remove(activeClass);
        lightbox.setAttribute('aria-hidden', 'true');
    };

    const closeButton = document.querySelector(closeSelector);

    if (closeButton) {
        closeButton.addEventListener('click', close);
    }

    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}
