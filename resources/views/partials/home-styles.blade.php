<style>
    .public-home { max-width: 1280px; padding: 16px; }
    .public-home > header { gap: 16px 24px; margin-bottom: 24px; }
    .public-home > header > a { font-weight: 700; white-space: nowrap; }
    .public-home > main { padding: 16px; }
    .public-home .catalog-navigation { gap: 16px; }
    .public-home .catalog-navigation [aria-current] { font-weight: 700; text-underline-offset: 6px; }
    .public-home .catalog-search { display: flex; align-items: center; gap: 8px; order: 1; flex: 1 0 100%; min-width: 0; }
    .public-home .search-field { flex: 1; min-width: 0; }
    .public-home .search-field label { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
    .public-home .catalog-search input { margin: 0; }
    .public-home .catalog-hero { padding: 8px 0 24px; }
    .public-home .catalog-hero h1 { margin: 0; font-size: clamp(1.5rem, 3vw, 2rem); line-height: 1.2; }
    .public-home .catalog-hero p { margin: 8px 0 0; }
    .public-home h2 { margin: 0; font-size: 1.25rem; }
    .public-home .featured-stores { display: grid; gap: 16px; margin-bottom: 32px; }
    .public-home .catalog-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .public-home .catalog-card { display: flex; flex-direction: column; gap: 8px; min-width: 0; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; overflow-wrap: anywhere; }
    .public-home .catalog-card h3, .public-home .catalog-card p { margin: 0; }
    .public-home .catalog-card > a:last-child { margin-top: auto; padding-top: 8px; }
    .public-home .catalog-card .catalog-image, .public-home .catalog-card .image-placeholder { width: 100%; height: 144px; object-fit: contain; background: #f1f5f9; border-radius: 4px; }
    .public-home .catalog-card .image-placeholder { display: grid; place-items: center; padding: 16px; }
    .public-home .catalog-toolbar { display: flex; align-items: end; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
    .public-home .catalog-results-heading { flex: 1 0 180px; }
    .public-home .catalog-filter-panel { flex: 1 1 560px; min-width: 0; }
    .public-home .catalog-filter-panel summary { padding: 10px 0; font-weight: 600; cursor: pointer; }
    .public-home .catalog-filters { display: flex; align-items: end; gap: 12px; flex-wrap: wrap; min-width: 0; }
    .public-home .filter-field { flex: 1 1 120px; min-width: 0; }
    .public-home .filter-field label { margin: 0; font-size: .875rem; }
    .public-home .filter-field input, .public-home .filter-field select { min-width: 0; }
    .public-home .filter-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
    .public-home .filter-actions a { font-size: .875rem; }
    .public-home .catalog-empty { grid-column: 1 / -1; }
    .public-home :is(a, button, input, select, summary):focus-visible { outline: 3px solid #1d4ed8; outline-offset: 3px; }
    @media (min-width: 640px) {
        .public-home { padding: 24px; }
        .public-home > main { padding: 24px; }
        .public-home .catalog-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .public-home .catalog-filter-panel[open] > summary { display: none; }
    }
    @media (min-width: 1024px) {
        .public-home .catalog-search { order: 0; flex: 1 1 240px; }
        .public-home .catalog-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>
