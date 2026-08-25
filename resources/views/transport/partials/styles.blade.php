{{-- Shared Transport UI: settings theme + transport layout primitives --}}
@include('settings.partials.styles')
<style>
    /* ---- Uniform headers across Transport ---- */
    .settings-page .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .settings-page .page-header .eyebrow,
    .settings-page .page-header .crumb {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.9;
        margin-bottom: 0.25rem;
        color: rgba(255, 255, 255, 0.88) !important;
    }

    .settings-page .page-header .crumb a {
        color: inherit;
        text-decoration: none;
    }

    .settings-page .page-header .crumb a:hover {
        text-decoration: underline;
    }

    .settings-page .page-header > .d-flex,
    .settings-page .page-header .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    /* ---- Alerts spacing ---- */
    .settings-page > .settings-shell > .alert {
        margin-bottom: 1rem;
    }

    /* ---- Filter / toolbar bars ---- */
    .transport-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: flex-end;
    }

    .transport-toolbar .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }

    /* ---- Stat strip ---- */
    .transport-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .transport-stat {
        background: var(--settings-surface, #fff);
        border: 1px solid var(--settings-border);
        border-radius: 14px;
        padding: 0.9rem 1rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }

    .transport-stat .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--settings-muted);
        margin-bottom: 0.2rem;
    }

    .transport-stat .value {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--settings-text);
        line-height: 1.2;
    }

    /* ---- Card board (vehicles / paired trips) ---- */
    .transport-board {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .transport-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .transport-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: linear-gradient(180deg, rgba(15, 118, 110, 0.06), transparent);
    }

    .transport-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.01em;
        color: var(--settings-text);
    }

    .transport-card-meta {
        color: #64748b;
        font-size: 0.9rem;
        margin-top: 0.2rem;
    }

    .transport-card-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .transport-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .transport-split-pane {
        padding: 1rem 1.25rem 1.15rem;
    }

    .transport-split-pane + .transport-split-pane {
        border-left: 1px solid rgba(15, 23, 42, 0.06);
    }

    .transport-pane-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .transport-pane-label strong {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
    }

    .transport-tile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #f8fafc;
        margin-bottom: 0.65rem;
    }

    .transport-tile:last-child {
        margin-bottom: 0;
    }

    .transport-tile-morning {
        border-left: 3px solid #0f766e;
        background: linear-gradient(90deg, rgba(15, 118, 110, 0.08), #f8fafc 42%);
    }

    .transport-tile-evening {
        border-left: 3px solid #0284c7;
        background: linear-gradient(90deg, rgba(2, 132, 199, 0.08), #f8fafc 42%);
    }

    .transport-tile-name {
        font-weight: 650;
        margin: 0;
        font-size: 0.98rem;
        color: var(--settings-text);
    }

    .transport-tile-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        justify-content: flex-end;
    }

    .transport-empty {
        color: #94a3b8;
        font-size: 0.9rem;
        padding: 0.75rem 0.25rem;
    }

    /* ---- Tables: sticky first column on small screens ---- */
    .settings-page .table-responsive {
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 14px 14px;
    }

    .settings-page .table-modern td .btn,
    .settings-page .table-modern th .btn {
        white-space: nowrap;
    }

    .settings-page .table-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        justify-content: flex-end;
    }

    /* ---- Forms: denser, consistent ---- */
    .settings-page .form-label.fw-semibold {
        color: var(--settings-text);
    }

    .settings-page .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid var(--settings-border);
    }

    /* ---- Mobile stacking ---- */
    @media (max-width: 900px) {
        .transport-split {
            grid-template-columns: 1fr;
        }

        .transport-split-pane + .transport-split-pane {
            border-left: 0;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }

        .settings-page .page-header h1 {
            font-size: 1.35rem;
        }

        .settings-shell {
            padding: 0 12px;
        }
    }

    @media (max-width: 576px) {
        .settings-page .page-header {
            padding: 16px 16px;
            border-radius: 14px;
        }

        .transport-tile {
            align-items: flex-start;
            flex-direction: column;
        }

        .transport-tile-actions {
            width: 100%;
        }

        .transport-tile-actions .btn {
            flex: 1 1 auto;
        }
    }

    /* ---- Dark mode ---- */
    .theme-dark .transport-card {
        background: #1e293b;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: none;
    }

    .theme-dark .transport-card-head,
    .theme-dark .transport-split-pane + .transport-split-pane {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .theme-dark .transport-tile {
        background: #0f172a;
        border-color: rgba(255, 255, 255, 0.1);
    }

    .theme-dark .transport-tile-morning {
        background: linear-gradient(90deg, rgba(15, 118, 110, 0.22), #0f172a 45%);
    }

    .theme-dark .transport-tile-evening {
        background: linear-gradient(90deg, rgba(2, 132, 199, 0.2), #0f172a 45%);
    }

    .theme-dark .transport-card-meta,
    .theme-dark .transport-pane-label strong,
    .theme-dark .transport-empty {
        color: #94a3b8;
    }

    .theme-dark .transport-stat {
        background: #1e293b;
        border-color: rgba(255, 255, 255, 0.08);
    }

    .theme-dark .transport-stat .value,
    .theme-dark .transport-card-title,
    .theme-dark .transport-tile-name {
        color: #e2e8f0;
    }

    .transport-tabs {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .transport-tabs a,
    .transport-tabs .transport-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 0.9rem;
        border-radius: 999px;
        border: 1px solid var(--settings-border, #e2e8f0);
        background: #fff;
        color: inherit;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s;
    }

    .transport-tabs a:hover,
    .transport-tabs .transport-tab:hover {
        background: rgba(15, 118, 110, 0.08);
    }

    .transport-tabs a.active,
    .transport-tabs .transport-tab.active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
    }

    .transport-search-results {
        position: absolute;
        z-index: 20;
        left: 0;
        right: 0;
        top: 100%;
        margin-top: 0.25rem;
        background: #fff;
        border: 1px solid var(--settings-border, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        max-height: 280px;
        overflow-y: auto;
    }

    .transport-search-results button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.7rem 0.9rem;
        border: 0;
        background: transparent;
        cursor: pointer;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .transport-search-results button:hover {
        background: rgba(15, 118, 110, 0.08);
    }

    .assignment-row-incomplete {
        background: rgba(245, 158, 11, 0.08);
    }

    .theme-dark .transport-tabs a,
    .theme-dark .transport-tabs .transport-tab,
    .theme-dark .transport-search-results {
        background: #1e293b;
        border-color: rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
    }

    .theme-dark .transport-search-results button {
        color: #e2e8f0;
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }
</style>
