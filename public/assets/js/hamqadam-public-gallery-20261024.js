(function () {
  'use strict';
  function initialize(gallery) {
    const main = gallery.querySelector('[data-member-gallery-main]');
    const counter = gallery.querySelector('[data-gallery-counter]');
    const privateBadge = gallery.querySelector('[data-gallery-private]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-member-gallery-thumb]'));
    if (!main || !thumbs.length) return;
    let index = 0;
    function show(next) {
      index = (next + thumbs.length) % thumbs.length;
      const selected = thumbs[index];
      const image = selected.querySelector('img');
      if (!image) return;
      main.src = image.currentSrc || image.src;
      main.alt = selected.dataset.photoName || 'Member gallery photo';
      if (privateBadge) privateBadge.hidden = index !== 0;
      thumbs.forEach((button, i) => {
        const active = i === index;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
      if (counter) counter.textContent = String(index + 1) + ' / ' + String(thumbs.length);
      selected.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }
    thumbs.forEach((button, i) => button.addEventListener('click', () => show(i)));
    gallery.querySelector('[data-gallery-previous]')?.addEventListener('click', () => show(index - 1));
    gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(index + 1));
    gallery.addEventListener('keydown', event => {
      if (event.key === 'ArrowRight') { event.preventDefault(); show(index + 1); }
      if (event.key === 'ArrowLeft') { event.preventDefault(); show(index - 1); }
    });
  }
  function boot() { document.querySelectorAll('[data-member-gallery]').forEach(initialize); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
