(function () {
    'use strict';

    var popup  = document.getElementById('txluyen-cf-popup');
    var opener = document.querySelector('.txluyen-cf-banggia');

    if (!popup || !opener) return;

    opener.addEventListener('click', function (e) {
        e.preventDefault();
        openPopup();
    });

    popup.addEventListener('click', function (e) {
        if (e.target === popup) closePopup();
    });

    var closeBtn = popup.querySelector('.txluyen-cf-popup-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closePopup);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePopup();
    });

    function openPopup() {
        popup.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        var close = popup.querySelector('.txluyen-cf-popup-close');
        if (close) close.focus();
    }

    function closePopup() {
        popup.setAttribute('hidden', '');
        document.body.style.overflow = '';
        opener.focus();
    }
})();
