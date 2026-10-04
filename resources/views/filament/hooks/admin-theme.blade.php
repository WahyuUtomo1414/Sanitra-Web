<style>
    :root {
        --sanitra-primary-950: #052e16;
        --sanitra-primary-900: #14532d;
        --sanitra-primary-800: #166534;
        --sanitra-primary-700: #15803d;
        --sanitra-primary-600: #16a34a;
        --sanitra-primary-500: #22c55e;
        --sanitra-accent-500: #f59e0b;
        --sanitra-accent-600: #d97706;
        --sanitra-slate-800: #1e293b;
        --sanitra-slate-700: #334155;
        --sanitra-slate-600: #475569;
        --sanitra-slate-500: #64748b;
        --sanitra-border: #e2e8f0;
    }

    html.fi,
    html.fi.dark {
        color-scheme: light;
    }

    .fi-body {
        --topbar-height: 5rem;
    }

    /* Topbar putih bersih dan seamless */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 1px solid var(--sanitra-border) !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02) !important;
        position: relative;
    }

    @media (min-width: 64rem) {
        /* Divider vertikal sejajar dengan batas sidebar desktop */
        .fi-topbar::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            inset-inline-start: var(--sidebar-width);
            width: 1px;
            background-color: var(--sanitra-border);
            z-index: 2;
        }

        .fi-body-has-topbar .fi-sidebar-header-ctn {
            display: none;
        }

        /* Reset flex container topbar-start agar child bisa absolute penuh */
        .fi-topbar-start {
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Penempatan logo pada topbar desktop — 100% presisi di tengah kolom sidebar */
        .fi-topbar-start > a {
            position: absolute !important;
            inset-inline-start: 0 !important;
            top: 0 !important;
            bottom: 0 !important;
            width: var(--sidebar-width) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            padding: 0 1.25rem !important;
            z-index: 3 !important;
            transform: none !important;
        }
    }

    .fi-logo {
        height: 3.15rem !important;
        max-height: 3.25rem !important;
        width: auto !important;
        object-fit: contain;
        margin-inline-start: 0 !important;
        margin-inline-end: 0 !important;
    }

    .fi-topbar-start .fi-logo {
        margin: 0 auto !important;
    }

    /* Logo di halaman login — ukuran lebih compact, proporsional, dan berjarak lega ke form */
    .fi-simple-header .fi-logo,
    .fi-simple-main-ctn .fi-logo,
    .fi-simple-page .fi-logo {
        height: 2.75rem !important;
        max-height: 2.85rem !important;
        width: auto !important;
        margin: 0 auto 1.75rem auto !important;
        display: block !important;
    }

    .fi-simple-header {
        margin-bottom: 1.25rem !important;
    }

    .fi-simple-header-heading {
        margin-top: 0 !important;
        margin-bottom: 0.5rem !important;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: var(--sanitra-slate-800) !important;
    }

    .fi-topbar-start .fi-logo,
    .fi-sidebar-header-logo-ctn .fi-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--sanitra-slate-800);
        line-height: 1.2;
    }

    .fi-topbar-start,
    .fi-topbar-start a,
    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-open-sidebar-btn,
    .fi-topbar .fi-topbar-close-sidebar-btn {
        color: var(--sanitra-slate-600) !important;
    }

    .fi-topbar .fi-icon-btn:hover,
    .fi-topbar .fi-topbar-open-sidebar-btn:hover,
    .fi-topbar .fi-topbar-close-sidebar-btn:hover {
        color: var(--sanitra-primary-600) !important;
    }

    /* Sidebar nav & footer — putih bersih dengan border halus */
    .fi-sidebar,
    .fi-sidebar-nav,
    .fi-sidebar-footer {
        background: #ffffff !important;
    }

    .fi-sidebar {
        border-inline-end: 1px solid var(--sanitra-border) !important;
        box-shadow: 1px 0 3px 0 rgba(0, 0, 0, 0.02) !important;
    }

    /* Header sidebar mobile */
    .fi-sidebar-header,
    .fi-sidebar-header-ctn {
        background: #ffffff !important;
        border-bottom: 1px solid var(--sanitra-border);
    }

    .fi-body-has-topbar .fi-sidebar-header {
        height: 5rem;
        padding: 0.5rem 1rem;
    }

    .fi-sidebar-header-logo-ctn {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .fi-sidebar-header-logo-ctn,
    .fi-sidebar-header-logo-ctn a,
    .fi-sidebar-header-logo-ctn .fi-logo,
    .fi-sidebar-header-logo-ctn .fi-logo * {
        color: inherit !important;
    }

    /* Label group (Perusahaan, Item, Project, dll.) — slate-500 uppercase */
    .fi-sidebar-group-label {
        color: var(--sanitra-slate-500) !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.06em !important;
        text-transform: uppercase !important;
    }

    .fi-sidebar-group-btn,
    .fi-sidebar-item-btn,
    .fi-sidebar-group-dropdown-trigger-btn {
        border-radius: 0.625rem !important;
        transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease !important;
    }

    /* Teks dan ikon menu default (inaktif) */
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-btn,
    .fi-sidebar-group-btn,
    .fi-sidebar-group-collapse-btn,
    .fi-sidebar-group-dropdown-trigger-btn {
        color: var(--sanitra-slate-700) !important;
    }

    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-label,
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-icon,
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) svg,
    .fi-sidebar-group svg,
    .fi-sidebar-open-collapse-sidebar-btn,
    .fi-sidebar-close-collapse-sidebar-btn {
        color: var(--sanitra-slate-600) !important;
        stroke: currentColor;
    }

    /* Hover State: Background mint lembut + teks & icon hijau */
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-btn:hover,
    .fi-sidebar-group-btn:hover,
    .fi-sidebar-group-dropdown-trigger-btn:hover {
        background-color: #f0fdf4 !important;
        color: var(--sanitra-primary-700) !important;
    }

    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-btn:hover .fi-sidebar-item-label,
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-btn:hover .fi-sidebar-item-icon,
    .fi-sidebar-item:not(.fi-active):not(.fi-sidebar-item-has-active-child-items) .fi-sidebar-item-btn:hover svg {
        color: var(--sanitra-primary-700) !important;
        stroke: var(--sanitra-primary-700) !important;
    }

    /* Active State (Opsi A): Background hijau segar + teks putih + strip kuning/amber di kiri */
    .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn {
        background-color: var(--sanitra-primary-600) !important;
        color: #ffffff !important;
        box-shadow: inset 4px 0 0 var(--sanitra-accent-500), 0 2px 4px 0 rgba(22, 163, 74, 0.25) !important;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-btn .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active .fi-sidebar-item-btn .fi-sidebar-item-icon,
    .fi-sidebar-item.fi-active .fi-sidebar-item-btn svg,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn .fi-sidebar-item-label,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn .fi-sidebar-item-icon,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn svg {
        color: #ffffff !important;
        stroke: #ffffff !important;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-btn:hover,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn:hover {
        background-color: var(--sanitra-primary-700) !important;
        color: #ffffff !important;
    }

    /* Primary button — hijau segar senada active menu */
    .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-ac .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-modal .fi-btn.fi-color-primary:not(.fi-outlined) {
        background: var(--sanitra-primary-600) !important;
        border-color: var(--sanitra-primary-600) !important;
        color: #ffffff !important;
    }

    .fi-btn.fi-color-primary:not(.fi-outlined):hover,
    .fi-ac .fi-btn.fi-color-primary:not(.fi-outlined):hover,
    .fi-modal .fi-btn.fi-color-primary:not(.fi-outlined):hover {
        background: var(--sanitra-primary-700) !important;
        border-color: var(--sanitra-primary-700) !important;
        color: #ffffff !important;
    }

    .fi-btn.fi-color-primary.fi-outlined {
        border-color: var(--sanitra-primary-600) !important;
        color: var(--sanitra-primary-600) !important;
    }

    .fi-icon-btn.fi-color-primary,
    .fi-link.fi-color-primary {
        color: var(--sanitra-primary-600) !important;
    }
</style>
