<style>
    :root {
        --sanitra-primary-950: #052e16;
        --sanitra-primary-900: #14532d;
        --sanitra-primary-800: #166534;
        --sanitra-primary-700: #15803d;
        --sanitra-primary-600: #16a34a;
        --sanitra-sage-700: #556b47;
        --sanitra-sage-500: #87a96b;
        --sanitra-sage-300: #b7c4a4;
        --sanitra-accent-500: #f59e0b;
        --sanitra-accent-100: #fef3c7;
    }

    html.fi,
    html.fi.dark {
        color-scheme: light;
    }

    .fi-body {
        --topbar-height: 5rem;
    }

    /* Topbar putih dengan bidang brand selebar sidebar */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 0;
        box-shadow: none !important;
        position: relative;
    }

    @media (min-width: 64rem) {
        .fi-topbar {
            background-image: linear-gradient(
                to right,
                var(--sanitra-primary-950) 0,
                var(--sanitra-primary-950) var(--sidebar-width),
                #ffffff var(--sidebar-width),
                #ffffff 100%
            ) !important;
        }

        .fi-body-has-topbar .fi-sidebar-header-ctn {
            display: none;
        }
    }

    .fi-topbar-start > a {
        position: absolute;
        inset-inline-start: calc(var(--sidebar-width) / 2);
        top: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        width: var(--sidebar-width);
    }

    .fi-topbar-start .fi-logo,
    .fi-sidebar-header-logo-ctn .fi-logo {
        height: auto !important;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .fi-topbar-start .fi-logo {
        color: #ffffff;
    }

    .fi-topbar-start,
    .fi-topbar-start a,
    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-open-sidebar-btn,
    .fi-topbar .fi-topbar-close-sidebar-btn {
        color: inherit;
    }

    /* Sidebar nav & footer — forest green tua */
    .fi-sidebar,
    .fi-sidebar-nav,
    .fi-sidebar-footer {
        background: var(--sanitra-primary-950) !important;
    }

    /* Header sidebar hanya tampil saat sidebar menjadi drawer di layar kecil */
    .fi-sidebar-header,
    .fi-sidebar-header-ctn {
        background: #ffffff !important;
    }

    .fi-body-has-topbar .fi-sidebar-header {
        height: 5rem;
        padding: 0.5rem;
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

    /* Teks dan ikon di nav — putih di atas forest green */
    .fi-sidebar-group-label,
    .fi-sidebar-item-label,
    .fi-sidebar-item-btn,
    .fi-sidebar-group-btn,
    .fi-sidebar-group-collapse-btn,
    .fi-sidebar-group-dropdown-trigger-btn,
    .fi-sidebar-item-icon,
    .fi-sidebar-group svg,
    .fi-sidebar-item svg,
    .fi-sidebar-open-collapse-sidebar-btn,
    .fi-sidebar-close-collapse-sidebar-btn {
        color: #ffffff !important;
        stroke: currentColor;
    }

    /* Label group pakai sage pale biar hierarki kebaca */
    .fi-sidebar-group-label {
        color: rgba(183, 196, 164, 0.78) !important;
    }

    .fi-sidebar-group-btn,
    .fi-sidebar-item-btn,
    .fi-sidebar-group-dropdown-trigger-btn {
        border-radius: 0.75rem;
    }

    .fi-sidebar-item-btn:hover,
    .fi-sidebar-group-btn:hover,
    .fi-sidebar-group-dropdown-trigger-btn:hover,
    .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn {
        background: rgba(255, 255, 255, 0.12) !important;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-btn,
    .fi-sidebar-item.fi-sidebar-item-has-active-child-items .fi-sidebar-item-btn {
        box-shadow: inset 3px 0 0 var(--sanitra-accent-500);
    }

    /* Primary button — forest green */
    .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-ac .fi-btn.fi-color-primary:not(.fi-outlined),
    .fi-modal .fi-btn.fi-color-primary:not(.fi-outlined) {
        background: var(--sanitra-primary-800) !important;
        border-color: var(--sanitra-primary-800) !important;
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
        border-color: var(--sanitra-primary-800) !important;
        color: var(--sanitra-primary-800) !important;
    }

    .fi-icon-btn.fi-color-primary,
    .fi-link.fi-color-primary {
        color: var(--sanitra-primary-800) !important;
    }
</style>
