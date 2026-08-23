@once
<style>
  .dashboard-page {
    --dash-primary: var(--brand-primary, #3a1a59);
    --dash-primary-dark: var(--brand-primary-dark, #2e1344);
    --dash-accent: var(--brand-accent, #6d28d9);
    --dash-bg: var(--brand-bg, #f5f7fb);
    --dash-surface: var(--brand-surface, #ffffff);
    --dash-border: var(--brand-border, #e5e7eb);
    --dash-text: var(--brand-text, #0f172a);
    --dash-muted: var(--brand-muted, #6b7280);
    background: var(--dash-bg);
    padding: 12px 0 32px;
  }
  body.theme-dark .dashboard-page {
    --dash-bg: #0b1220;
    --dash-surface: #111827;
    --dash-border: #1f2937;
    --dash-text: #e5e7eb;
    --dash-muted: #9ca3af;
  }
  .dashboard-shell {
    max-width: 1600px;
    width: 100%;
    margin: 0 auto;
    padding: 0 20px;
  }
  .dash-hero {
    background: var(--dash-surface);
    color: var(--dash-text);
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid var(--dash-border);
    border-left: 4px solid var(--dash-primary);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
  }
  .dash-hero h1, .dash-hero h2, .dash-hero h3 { margin: 0; font-weight: 700; color: var(--dash-text); }
  .dash-hero .crumb { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--dash-primary); margin-bottom: 6px; display: block; }
  .dash-hero p { color: var(--dash-muted); }
  .dash-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: color-mix(in srgb, var(--dash-primary) 8%, #fff 92%);
    border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, var(--dash-border) 82%);
    border-radius: 999px;
    color: var(--dash-primary);
    font-weight: 600;
    font-size: 13px;
  }
  .dash-hero .actions { display: flex; gap: 10px; flex-wrap: wrap; }
  .dash-card,
  .dashboard-page .card {
    background: var(--dash-surface);
    border: 1px solid var(--dash-border);
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
  }
  .dash-card .card-header,
  .dashboard-page .card .card-header {
    background: linear-gradient(90deg, rgba(20, 184, 166, 0.08), rgba(15, 118, 110, 0.06));
    border-bottom: 1px solid var(--dash-border);
    border-radius: 14px 14px 0 0;
    font-weight: 700;
    color: var(--dash-primary);
  }
  .dash-card .card-body,
  .dashboard-page .card .card-body { padding: 18px; }
  .dash-card .card-footer,
  .dashboard-page .card .card-footer { border-top: 1px solid var(--dash-border); background: transparent; }
  .dash-kpi-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--dash-primary) 12%, #fff 88%);
    color: var(--dash-primary);
  }
  .dash-delta { font-size: 13px; font-weight: 600; }
  .dash-delta.up { color: #16a34a; }
  .dash-delta.down { color: #dc2626; }
  .dash-table thead th {
    background: color-mix(in srgb, var(--dash-primary) 6%, #fff 94%);
    border-bottom: 1px solid var(--dash-border);
    color: var(--dash-text);
    font-weight: 700;
  }
  .dash-table td, .dash-table th { vertical-align: middle; }
  .dash-pill {
    border-radius: 999px;
    padding: 6px 10px;
    font-weight: 600;
    font-size: 12px;
    border: 1px solid var(--dash-border);
    background: color-mix(in srgb, var(--dash-primary) 8%, #fff 92%);
    color: var(--dash-primary);
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .dash-muted { color: var(--dash-muted); }
  .dash-badge-soft {
    border-radius: 10px;
    padding: 6px 10px;
    background: color-mix(in srgb, var(--dash-primary) 8%, #fff 92%);
    color: var(--dash-primary);
    border: 1px solid color-mix(in srgb, var(--dash-primary) 18%, var(--dash-border) 82%);
    font-weight: 600;
  }
  .dash-section {
    margin-top: 18px;
  }
  .dash-list-item {
    border-bottom: 1px solid var(--dash-border);
    padding: 10px 0;
  }
  .dash-list-item:last-child { border-bottom: none; }
  .dash-btn-ghost {
    background: color-mix(in srgb, var(--dash-primary) 8%, #fff 92%);
    border: 1px solid color-mix(in srgb, var(--dash-primary) 20%, var(--dash-border) 80%);
    color: var(--dash-primary);
    border-radius: 12px;
    padding: 8px 12px;
    font-weight: 600;
  }
  .dash-btn-ghost:hover { background: color-mix(in srgb, var(--dash-primary) 14%, #fff 86%); }
  .dashboard-page .table-hover tbody tr:hover {
    background: color-mix(in srgb, var(--dash-primary) 6%, #fff 94%);
  }
  .dashboard-page .list-group-item { border-color: var(--dash-border); }
  .dashboard-page .badge.bg-light { color: var(--dash-text); border: 1px solid var(--dash-border); }
  .dash-section-label {
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--dash-muted);
    font-weight: 700;
    margin: 8px 0 10px;
  }
  .dash-filters .form-select, .dash-filters .form-control { min-height: 38px; }
  .erp-kpi { min-height: 108px; }
  .erp-kpi-value { font-size: 1.35rem; font-weight: 700; color: var(--dash-text); letter-spacing: -0.02em; }
  .erp-overview-item {
    padding: 12px;
    border: 1px solid var(--dash-border);
    border-radius: 12px;
    background: color-mix(in srgb, var(--dash-primary) 4%, #fff 96%);
  }
  .erp-overview-item .value { font-weight: 700; font-size: 1.15rem; }
  .erp-empty { padding: 28px 16px; text-align: center; color: var(--dash-muted); }
  .erp-empty i { font-size: 1.4rem; display: block; margin-bottom: 8px; opacity: 0.7; }
  .erp-quick-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 10px; }
  .erp-quick-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 8px; min-height: 84px; padding: 12px 8px; border-radius: 14px;
    border: 1px solid var(--dash-border); background: var(--dash-surface);
    color: var(--dash-text); text-decoration: none; font-weight: 600; font-size: 12px; text-align: center;
  }
  .erp-quick-btn:hover { background: color-mix(in srgb, var(--dash-primary) 8%, #fff 92%); color: var(--dash-primary); }
  .erp-quick-btn i { font-size: 1.25rem; color: var(--dash-primary); }
  .erp-status { display: inline-flex; border-radius: 999px; padding: 3px 8px; font-size: 11px; font-weight: 700; }
  .erp-status.due, .erp-status.outstanding, .erp-status.scheduled { background: #fef3c7; color: #92400e; }
  .erp-status.overdue, .erp-status.missing-driver, .erp-status.missing-vehicle { background: #fee2e2; color: #991b1b; }
  .erp-status.partial { background: #dbeafe; color: #1e3a8a; }
  .erp-kv { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; }
  .erp-invoice-cards { display: none; }
  .dashboard-shell { overflow-x: hidden; max-width: 100%; }
  .dashboard-page .btn { min-height: 40px; }
  .erp-error {
    padding: 20px 16px;
    text-align: center;
    color: var(--dash-muted);
  }
  @media (max-width: 991.98px) {
    .dashboard-shell { padding: 0 12px; }
    .erp-invoice-table { display: none; }
    .erp-invoice-cards { display: grid; gap: 10px; padding: 12px; }
    .erp-invoice-card {
      border: 1px solid var(--dash-border);
      border-radius: 12px;
      padding: 12px;
      background: var(--dash-surface);
    }
    .erp-kpi-value { font-size: 1.2rem; }
    canvas { max-height: 220px !important; }
    .dashboard-page .btn { min-height: 44px; }
    .erp-quick-btn { min-height: 92px; }
  }
  @media (max-width: 430px) {
    .dashboard-page { padding-bottom: 24px; }
    .dash-hero h2 { font-size: 1.25rem; }
  }
</style>
@endonce

