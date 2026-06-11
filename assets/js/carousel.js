document.addEventListener('DOMContentLoaded', function() {
    const carouselInner = document.querySelector('.carousel-inner');
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.querySelector('.carousel-control.prev');
    const nextBtn = document.querySelector('.carousel-control.next');
    let currentIndex = 0;

    function updateCarousel() {
        carouselInner.style.transform = `translateX(-${currentIndex * 50}%)`;
    }

    prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        updateCarousel();
    });

    nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % items.length;
        updateCarousel();
    });

    //Défilement automatique
    setInterval(() => {
        currentIndex = (currentIndex + 1) % items.length;
        updateCarousel();
    }, 5000);
});
