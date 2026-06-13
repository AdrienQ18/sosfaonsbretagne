import {onReady} from './dom.js';
import {initImageLightbox} from './lightbox.js';

onReady(() => {
    initImageLightbox({
        triggerSelector: '.gallery-image',
        lightboxSelector: '#gallery-lightbox',
        imageSelector: '#gallery-lightbox-image',
        closeSelector: '.gallery-lightbox-close',
    });
});

