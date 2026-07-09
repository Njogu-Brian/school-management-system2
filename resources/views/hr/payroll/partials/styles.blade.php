{{-- Shared payroll UI: readable dropdowns, even buttons, mobile layout --}}
@once
<style>
    /* Header action toolbar */
    .payroll-page .page-header {
        overflow: visible;
    }

    .payroll-page .payroll-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        max-width: 100%;
    }

    .payroll-page .payroll-actions > .btn,
    .payroll-page .payroll-actions .btn-group > .btn,
    .payroll-page .payroll-actions form .btn,
    .payroll-page .page-header > .d-flex > .btn,
    .payroll-page .page-header .btn {
        min-height: 40px;
        padding: 0.45rem 0.9rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
    }

    .payroll-page .payroll-actions .form-select,
    .payroll-page .page-header .form-select {
        min-height: 40px;
        font-size: 0.875rem;
        width: auto;
        min-width: 11rem;
        max-width: 100%;
    }

    .payroll-page .payroll-pay-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    /* Readable dropdowns on gradient headers (fix white-on-white links) */
    .payroll-page .page-header .dropdown-menu,
    .payroll-page .payroll-actions .dropdown-menu {
        background: #ffffff !important;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
        padding: 0.4rem;
        min-width: 16rem;
        z-index: 1080;
    }

    .payroll-page .page-header .dropdown-menu .dropdown-item,
    .payroll-page .payroll-actions .dropdown-menu .dropdown-item,
    .payroll-page .page-header .dropdown-menu a.dropdown-item {
        color: #0f172a !important;
        border-radius: 8px;
        padding: 0.55rem 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        white-space: normal;
    }

    .payroll-page .page-header .dropdown-menu .dropdown-item:hover,
    .payroll-page .page-header .dropdown-menu .dropdown-item:focus,
    .payroll-page .payroll-actions .dropdown-menu .dropdown-item:hover,
    .payroll-page .payroll-actions .dropdown-menu .dropdown-item:focus {
        background: #f0fdfa !important;
        color: #0f766e !important;
    }

    .payroll-page .page-header .dropdown-toggle::after {
        margin-left: 0.4rem;
    }

    /* Stats + tables */
    .payroll-page .stat-card h3 {
        font-size: clamp(1.1rem, 2.5vw, 1.5rem);
        word-break: break-word;
    }

    .payroll-page .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    .payroll-page .table-modern {
        min-width: 640px;
    }

    .payroll-page .table-modern th,
    .payroll-page .table-modern td {
        white-space: nowrap;
    }

    .payroll-page .table-modern td .fw-semibold,
    .payroll-page .table-modern td .small {
        white-space: normal;
    }

    /* Mobile */
    @media (max-width: 767.98px) {
        .payroll-page .settings-shell {
            padding: 0 12px;
        }

        .payroll-page .page-header {
            padding: 16px;
            border-radius: 14px;
        }

        .payroll-page .page-header h1 {
            font-size: 1.25rem;
        }

        .payroll-page .payroll-actions,
        .payroll-page .page-header > .d-flex.flex-wrap,
        .payroll-page .page-header .d-flex.gap-2 {
            width: 100%;
        }

        .payroll-page .payroll-actions > .btn,
        .payroll-page .payroll-actions .btn-group,
        .payroll-page .payroll-actions form,
        .payroll-page .page-header .btn,
        .payroll-page .page-header .btn-group {
            width: 100%;
        }

        .payroll-page .payroll-actions .btn-group > .btn,
        .payroll-page .payroll-pay-form .btn,
        .payroll-page .payroll-pay-form .form-select {
            width: 100%;
            min-width: 0;
        }

        .payroll-page .payroll-actions .dropdown-menu {
            width: 100%;
            min-width: 0;
        }

        .payroll-page .settings-card .card-header,
        .payroll-page .settings-card .card-body {
            padding: 14px;
        }

        .payroll-page .row.g-3 > [class*="col-"] {
            flex: 0 0 50%;
            max-width: 50%;
        }

        .payroll-page .filter-row .col-md-4,
        .payroll-page .filter-row .col-md-3,
        .payroll-page .filter-row .col-md-2,
        .payroll-page form.row > [class*="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .payroll-page .btn-sm {
            min-height: 36px;
        }
    }

    @media (max-width: 575.98px) {
        .payroll-page .row.g-3 > [class*="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .payroll-page .table-modern {
            min-width: 520px;
            font-size: 0.85rem;
        }
    }
</style>
@endonce
