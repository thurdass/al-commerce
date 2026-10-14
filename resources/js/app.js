const desktop = window.matchMedia('(min-width: 768px)');

for (const panel of document.querySelectorAll('[data-responsive-filters]')) {
    const syncFilters = () => { panel.open = desktop.matches; };
    syncFilters();
    desktop.addEventListener('change', syncFilters);
}

for (const gallery of document.querySelectorAll('[data-product-gallery]')) {
    const mainImage = gallery.querySelector('[data-gallery-main]');
    const thumbnails = gallery.querySelectorAll('[data-gallery-thumbnail]');

    for (const thumbnail of thumbnails) {
        thumbnail.addEventListener('click', () => {
            mainImage.src = thumbnail.dataset.src;
            mainImage.alt = thumbnail.dataset.alt;
            for (const item of thumbnails) {
                item.setAttribute('aria-pressed', String(item === thumbnail));
            }
        });
    }
}
