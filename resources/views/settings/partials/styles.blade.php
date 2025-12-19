@once
@php
    $brandPrimary = setting('finance_primary_color', '#0f766e');
    $brandPrimaryDark = setting('finance_primary_color', '#0b5c54');
    $brandAccent = setting('finance_secondary_color', '#14b8a6');
    $brandBg = '#f5f7fb';
    $brandBorder = '#e5e7eb';
    $brandText = '#0f172a';
    $brandMuted = '#6b7280';
@endphp
<style>
    /* Settings UI theme inspired by styles.md */
    .settings-page {
        --settings-primary: {{ $brandPrimary }};
        --settings-primary-dark: {{ $brandPrimaryDark }};
        --settings-accent: {{ $brandAccent }};
        --settings-bg: {{ $brandBg }};
        --settings-surface: var(--brand-surface, #ffffff);
        --settings-border: {{ $brandBorder }};
        --settings-text: {{ $brandText }};
        --settings-muted: {{ $brandMuted }};
        --settings-card-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        background: var(--settings-bg);
        padding: 12px 0 32px;
    }

    .settings-shell {
        max-width: 1600px;
        width: 100%;
        margin: 0 auto;
        padding: 0 20px;
    }

    .settings-page .page-header {
        background: linear-gradient(135deg, var(--settings-primary) 0%, var(--settings-accent) 100%);
        color: #e6fffa;
        border-radius: 18px;
        padding: 22px 24px;
        box-shadow: var(--settings-card-shadow);
    }

    .settings-page .page-header .crumb {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.85;
        margin-bottom: 4px;
    }

    .settings-page .page-header h1 {
        font-size: 26px;
        margin: 0;
        font-weight: 700;
    }

    .settings-page .page-header p {
        margin: 6px 0 0;
        color: #e0f7f4;
    }

    .settings-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        color: #e8fffb;
        font-weight: 600;
        font-size: 13px;
        backdrop-filter: blur(8px);
    }

    .settings-tabs {
        border: 0;
        margin: 18px 0 10px;
        gap: 10px;
        flex-wrap: wrap;
    }

    .settings-tabs .nav-link {
        border: 1px solid var(--settings-border);
        background: #fff;
        color: var(--settings-text);
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .settings-tabs .nav-link i {
        font-size: 18px;
        color: var(--settings-primary);
    }

    .settings-tabs .nav-link.active {
        background: var(--settings-primary);
        color: #fff;
        border-color: var(--settings-primary);
        box-shadow: 0 10px 24px rgba(14, 116, 110, 0.22);
    }

    .settings-tabs .nav-link.active i {
        color: #fff;
    }

    .tab-surface {
        background: var(--settings-surface, #fff);
        border: 1px solid var(--settings-border);
        border-radius: 14px;
        box-shadow: var(--settings-card-shadow);
        padding: 22px;
    }

    .settings-card {
        background: var(--settings-surface, #fff);
        border: 1px solid var(--settings-border);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        margin-bottom: 18px;
    }

    .settings-card .card-header {
        background: linear-gradient(90deg, rgba(20, 184, 166, 0.1), rgba(15, 118, 110, 0.08));
        border-bottom: 1px solid var(--settings-border);
        padding: 14px 18px;
        border-radius: 14px 14px 0 0;
    }

    .settings-card .card-header h5 {
        margin: 0;
        font-weight: 700;
        color: var(--settings-primary);
    }

    .settings-card .card-body {
        padding: 18px;
    }

    .settings-card .section-title {
        font-weight: 700;
        color: var(--settings-text);
        margin-bottom: 4px;
    }

    .settings-card .section-note {
        color: var(--settings-muted);
        font-size: 14px;
        margin-bottom: 14px;
    }

    .form-note {
        color: var(--settings-muted);
        font-size: 13px;
    }

    .input-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfeff;
        color: #0f172a;
        border: 1px solid #cffafe;
        font-weight: 600;
        font-size: 12px;
    }

    .btn-settings-primary {
        background: linear-gradient(135deg, var(--settings-primary), var(--settings-accent));
        border: none;
        color: #fff;
        box-shadow: 0 12px 24px rgba(20, 184, 166, 0.25);
    }

    .btn-settings-primary:hover {
        background: linear-gradient(135deg, var(--settings-primary-dark), var(--settings-primary));
        color: #fff;
    }

    .btn-ghost {
        background: var(--settings-surface, #fff);
        border: 1px solid var(--settings-border);
        color: var(--settings-text);
    }
    .btn-ghost-strong {
        background: color-mix(in srgb, var(--settings-primary) 8%, #ffffff 92%);
        border: 1px solid color-mix(in srgb, var(--settings-primary) 20%, var(--settings-border) 80%);
        color: var(--settings-primary);
        box-shadow: 0 8px 18px rgba(0,0,0,0.06);
    }
    .btn-ghost-strong:hover {
        background: color-mix(in srgb, var(--settings-primary) 14%, #ffffff 86%);
        color: var(--settings-primary);
        border-color: color-mix(in srgb, var(--settings-primary) 30%, var(--settings-border) 70%);
    }

    .pill-badge {
        border-radius: 999px;
        padding: 6px 10px;
        background: #f0fdf4;
        color: #166534;
        font-weight: 600;
        font-size: 12px;
        border: 1px solid #bbf7d0;
    }

    .table-modern thead th {
        background: #f8fafc;
        font-weight: 700;
        color: var(--settings-text);
        border-bottom: 1px solid var(--settings-border);
    }

    .table-modern td,
    .table-modern th {
        vertical-align: middle;
    }

    .placeholder-table code {
        background: #0f172a;
        color: #ecfeff;
        padding: 3px 8px;
        border-radius: 8px;
    }

    .subtle-hero {
        border: 1px dashed var(--settings-border);
        border-radius: 12px;
        padding: 14px 16px;
        background: #f0fdfa;
        color: #0f172a;
    }

    .mini-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid var(--settings-border);
        border-radius: 12px;
    }

    .gradient-preview {
        border-radius: 12px;
        padding: 14px;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    /* Dark mode overrides */
    .theme-dark .settings-page {
        --settings-bg: #0b1220;
        --settings-surface: #111827;
        --settings-border: #1f2937;
        --settings-text: #e5e7eb;
        --settings-muted: #9ca3af;
        --settings-card-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }
    .theme-dark .tab-surface,
    .theme-dark .settings-card,
    .theme-dark .btn-ghost {
        background: var(--settings-surface, #111827);
        color: var(--settings-text);
        border-color: var(--settings-border);
    }
    .theme-dark .settings-tabs .nav-link {
        background: var(--settings-surface, #111827);
        color: var(--settings-text);
        border-color: var(--settings-border);
    }
    .theme-dark .settings-tabs .nav-link.active {
        color: #fff;
    }
    .theme-dark .table-modern thead th {
        background: #111827;
    }
    .theme-dark .input-chip {
        background: #0f172a;
        border-color: #1f2937;
        color: #e5e7eb;
    }
    .theme-dark .pill-badge {
        background: #11211c;
        border-color: #1f2937;
        color: #c0e8db;
    }
</style>
@endonce

