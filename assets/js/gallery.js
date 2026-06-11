document.addEventListener('DOMContentLoaded', () => {
    initGalleryLightbox();

    function initGalleryLightbox() {
        const images = document.querySelectorAll('.gallery-image');
        const lightbox = document.getElementById('gallery-lightbox');
        const lightboxImage = document.getElementById('gallery-lightbox-image');
        const closeButton = document.querySelector('.gallery-lightbox-close');

        if (!lightbox) {
            return;
        }

        images.forEach((image) => {
            image.addEventListener('click', () => {
                lightboxImage.src = image.src;
                lightbox.classList.add('active');
            });
        });

        closeButton.addEventListener('click', () => {
            lightbox.classList.remove('active');
        });

        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) {
                lightbox.classList.remove('active');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                lightbox.classList.remove('active');
            }
        });
    }



});



