document.addEventListener('DOMContentLoaded', () => {

    const viewer = document.getElementById('image-viewer');
    const viewerImg = document.getElementById('image-viewer-img');
    const closeBtn = document.querySelector('.image-viewer-close');

    document.querySelectorAll('.js-image-viewer').forEach(image => {

        image.addEventListener('click', () => {

            viewerImg.src = image.src;
            viewer.classList.add('active');

        });

    });

    closeBtn.addEventListener('click', () => {
        viewer.classList.remove('active');
    });

    viewer.addEventListener('click', (e) => {

        if (e.target === viewer) {
            viewer.classList.remove('active');
        }

    });

});

