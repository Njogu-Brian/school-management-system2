@once
<style>
  /* Finance theming aligned to styles.md, using brand vars + dark mode */
  .finance-page {
    --fin-primary: var(--brand-primary, #0f766e);
    --fin-primary-dark: var(--brand-primary-dark, #0b5c54);
    --fin-accent: var(--brand-accent, #14b8a6);
    --fin-bg: var(--brand-bg, #f5f7fb);
    --fin-surface: var(--brand-surface, #ffffff);
    --fin-border: var(--brand-border, #e5e7eb);
    --fin-text: var(--brand-text, #0f172a);
    --fin-muted: var(--brand-muted, #6b7280);
    background: var(--fin-bg);
    padding: 12px 0 32px;
  }
  body.theme-dark .finance-page {
    --fin-bg: #0b1220;
    --fin-surface: #111827;
    --fin-border: #1f2937;
    --fin-text: #e5e7eb;
    --fin-muted: #9ca3af;
  }

  .finance-shell {
    max-width: 1600px;
    width: 100%;
    margin: 0 auto;
    padding: 0 20px;
  }

  .finance-hero {
    background: linear-gradient(135deg, var(--fin-primary) 0%, var(--fin-accent) 100%);
    color: #e6fffa;
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
    margin-bottom: 18px;
  }
  .finance-hero h1,.finance-hero h2,.finance-hero h3 { margin: 0; font-weight: 700; }
  .finance-hero p { margin: 4px 0 0; color: #e8fffb; }

  .finance-card,
  .finance-filter-card,
  .finance-table-wrapper,
  .finance-table-wrapper .card,
  .finance-card .card,
  .finance-card .card-body {
    background: var(--fin-surface);
    border: 1px solid var(--fin-border);
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
  }
  .finance-card {
    padding: 0;
    overflow: hidden;
  }
  .finance-card .card-header,
  .finance-filter-card .card-header {
    background: linear-gradient(90deg, rgba(20, 184, 166, 0.08), rgba(15, 118, 110, 0.06));
    border-bottom: 1px solid var(--fin-border);
    border-radius: 14px 14px 0 0;
    font-weight: 700;
    color: var(--fin-primary);
  }
  .finance-card .card-body { padding: 18px; }

  /* Ensure card headers have comfortable breathing room across all finance views */
  .finance-card-header,
  .finance-filter-card .card-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.3;
    min-height: 52px;
  }

  /* Extra body padding consistency */
  .finance-card-body {
    padding: 18px;
  }

  .finance-stat-card {
    background: var(--fin-surface);
    border: 1px solid var(--fin-border);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
  }

  .finance-form-control,
  .finance-form-select {
    width: 100%;
    border: 1px solid var(--fin-border);
    border-radius: 10px;
    padding: 10px 12px;
    color: var(--fin-text);
    background: var(--fin-surface);
  }
  .finance-form-label {
    font-weight: 600;
    color: var(--fin-text);
    margin-bottom: 6px;
  }

  .finance-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--fin-text);
  }
  .finance-table thead th {
    background: color-mix(in srgb, var(--fin-primary) 6%, #fff 94%);
    border-bottom: 1px solid var(--fin-border);
    font-weight: 700;
  }
  .finance-table td,
  .finance-table th {
    padding: 10px 12px;
    border-bottom: 1px solid var(--fin-border);
    vertical-align: middle;
  }
  .finance-table tbody tr:hover {
    background: color-mix(in srgb, var(--fin-primary) 4%, #fff 96%);
  }

  .finance-badge {
    border-radius: 999px;
    padding: 6px 10px;
    font-weight: 600;
    font-size: 12px;
    border: 1px solid var(--fin-border);
    background: color-mix(in srgb, var(--fin-primary) 8%, #fff 92%);
    color: var(--fin-primary);
  }
  .finance-badge.badge-paid { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
  .finance-badge.badge-partial { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
  .finance-badge.badge-unpaid { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

  .btn-finance,
  .btn-finance-primary,
  .btn-finance-outline {
    border-radius: 12px;
    font-weight: 600;
  }
  .btn-finance-primary {
    background: linear-gradient(135deg, var(--fin-primary), var(--fin-accent));
    border: none;
    color: #fff;
    box-shadow: 0 10px 20px rgba(20, 184, 166, 0.18);
  }
  .btn-finance-primary:hover {
    background: linear-gradient(135deg, var(--fin-primary-dark), var(--fin-primary));
    color: #fff;
  }
  .btn-finance-outline {
    background: color-mix(in srgb, var(--fin-primary) 8%, #fff 92%);
    border: 1px solid color-mix(in srgb, var(--fin-primary) 20%, var(--fin-border) 80%);
    color: var(--fin-primary);
  }

  .finance-action-buttons .btn {
    border-radius: 10px;
  }

  .finance-table-wrapper .table-responsive {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--fin-border);
  }

  .finance-filter-card {
    padding: 16px;
    margin-bottom: 18px;
  }

  .finance-animate { transition: all 0.25s ease; }
  .finance-animate:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0,0,0,0.06); }

  .finance-muted { color: var(--fin-muted); }
</style>
@endonce

