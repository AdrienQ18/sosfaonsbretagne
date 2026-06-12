import {onReady} from './dom.js';

onReady(() => {
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.querySelector('.carousel-control.prev');
    const nextBtn = document.querySelector('.carousel-control.next');

    if (!items.length || !prevBtn || !nextBtn) {
        return;
    }

    let current = 0;

    function updateCarousel() {

        items.forEach(item => {
            item.style.display = 'none';
            item.classList.remove('active');
        });

        const previous =
            (current - 1 + items.length) % items.length;

        const next =
            (current + 1) % items.length;

        items[previous].style.display = 'flex';
        items[current].style.display = 'flex';
        items[next].style.display = 'flex';

        items[current].classList.add('active');
    }

    prevBtn.addEventListener('click', () => {
        current = (current - 1 + items.length) % items.length;
        updateCarousel();
    });

    nextBtn.addEventListener('click', () => {
        current = (current + 1) % items.length;
        updateCarousel();
    });

    setInterval(() => {
        current = (current + 1) % items.length;
        updateCarousel();
    }, 5000);

    updateCarousel();
});
