<style>
    /* ---------- Report text blocks (summary / conclusion) and signature lines ---------- */
    .report-note { border: 1px solid #cfd8d3; background: #f7faf8; border-radius: .5rem; padding: .6rem .9rem; }
    .report-note .rn-title { font-weight: 700; font-size: .85rem; color: #1e5b31; margin-bottom: .25rem; }
    .report-note p { font-size: .8rem; margin: 0 0 .25rem; }
    .signature-row { justify-content: space-between; gap: 3rem; margin-top: 2.2rem; }
    .signature-row .sig { flex: 1; text-align: center; font-size: .8rem; }
    .signature-row .sig-line { border-top: 1px solid #333; margin-bottom: 4px; }
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
    @media (max-width: 575.98px) {
        .filter-bar { padding: .75rem; gap: .75rem; }
        .filter-bar .filter-group { flex-wrap: wrap; width: 100%; }
        .filter-bar .btn-group { width: 100%; }
        .filter-bar .btn-group .btn { flex: 1 1 0; min-width: 0; padding: .45rem .25rem; font-size: .95rem; }
        .filter-bar .range-field { flex: 1 1 100%; }
        .filter-bar .range-field label { min-width: 2.6rem; font-size: .85rem; }
        .filter-bar input[type=date] { flex: 1 1 auto; width: 100%; min-width: 0; }
        .filter-bar .range-sep { display: none; }
        .filter-bar form .btn-icon { width: 100%; }
        .report-note p { font-size: .95rem; }
        .report-note .rn-title { font-size: 1rem; }
    }

    /* ---------- Printing: same clean layout on both reports ---------- */
    @media print {
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { background: #fff !important; }
        .sidebar, .topbar, .no-print, .sidebar-backdrop { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; max-width: 100% !important; }

        /* The table may never be cut off at the edge of the page */
        .table-responsive { overflow: visible !important; }
        .card { box-shadow: none !important; border-color: #cfd8d3 !important; }
        table { width: 100% !important; font-size: 8.5pt; }
        .table > :not(caption) > * > * { padding: .3rem .4rem !important; }
        td.text-end { white-space: nowrap; }
        thead { display: table-header-group; }      /* header repeats on every printed page */
        tfoot { display: table-footer-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .badge-soft { padding: .15em .45em; font-size: 7.5pt; }

        /* Summary cards stay side by side, even on a portrait page */
        .print-row > .col-md-3 { width: 25%; }
        .print-row > .col-md-4 { width: 33.3333%; }
        .print-row > .col-md-8 { width: 66.6667%; }
        .print-row .card-body { padding: .6rem .8rem; }

        /* Page heading that only appears on paper */
        .print-header { border-bottom: 2px solid #1e5b31; padding-bottom: 6px; margin-bottom: 10px; }
        .print-header .ph-brand { font-size: 8pt; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #1e5b31; }
        .print-header .ph-title { font-size: 18pt; font-weight: 700; line-height: 1.2; }
        .print-header .ph-meta  { font-size: 8pt; color: #6b7a72; }
    }
</style>
