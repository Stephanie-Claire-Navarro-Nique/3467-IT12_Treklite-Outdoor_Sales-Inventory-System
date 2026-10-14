<style>
    /* ---------- Soft stat cards (easy on the eyes) ---------- */
    .stat-soft { border: 1px solid transparent; border-radius: .75rem; }
    .stat-soft .stat-label { font-size: .8rem; font-weight: 600; letter-spacing: .02em; }
    .stat-soft .stat-value { font-size: 1.5rem; font-weight: 700; }
    .stat-warn   { background: #fff8e6; border-color: #f4e1a6; }
    .stat-warn .stat-label   { color: #8a6100; }
    .stat-warn .stat-value   { color: #5c4200; }
    .stat-danger { background: #fdeeee; border-color: #f4c7c7; }
    .stat-danger .stat-label { color: #a4332f; }
    .stat-danger .stat-value { color: #6e1f1c; }

    /* ---------- Soft status badges ---------- */
    .badge-soft { font-weight: 600; padding: .35em .65em; border: 1px solid transparent; }
    .badge-soft-ok     { background: #e6f4ea; color: #1e5b31; border-color: #c5e3cd; }
    .badge-soft-warn   { background: #fff3cd; color: #7a5600; border-color: #f4e1a6; }
    .badge-soft-danger { background: #fde4e4; color: #a4332f; border-color: #f4c7c7; }

    /* ---------- Unified filter toolbar ---------- */
    .filter-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem;
        background: #fff; border: 1px solid #e3e8e5; border-radius: .75rem;
        padding: .75rem 1rem;
    }
    .filter-group { display: flex; align-items: center; gap: .5rem; }
    .filter-divider { width: 1px; align-self: stretch; background: #e3e8e5; }
    .filter-bar .btn-group .btn { min-width: 4rem; }
    .filter-bar .btn-outline-success { color: #1e5b31; border-color: #b9d3c1; }
    .filter-bar .btn-outline-success:hover { background: #e6f4ea; color: #1e5b31; }
    .filter-bar .btn-success { background: #1e5b31; border-color: #1e5b31; }
    .filter-bar .btn-outline-secondary { color: #4b5a52; border-color: #d3dbd6; }
    .filter-bar .btn-outline-secondary:hover { background: #f1f4f2; color: #1e5b31; }
    .filter-bar .range-field { display: flex; align-items: center; gap: .4rem; }
    .filter-bar .range-field label { font-size: .75rem; color: #6b7a72; margin: 0; }
    .filter-bar input[type=date] { width: auto; }
    .filter-bar .range-sep { color: #9aa7a0; }
    .filter-label { font-size: .75rem; font-weight: 600; color: #6b7a72; text-transform: uppercase; letter-spacing: .04em; }
    @media (max-width: 768px) { .filter-divider { display: none; } }
</style>
