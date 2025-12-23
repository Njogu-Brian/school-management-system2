@once
<style>
  .dashboard-page {
    --dash-primary: var(--brand-primary, #0f766e);
    --dash-primary-dark: var(--brand-primary-dark, #0b5c54);
    --dash-accent: var(--brand-accent, #14b8a6);
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
    background: linear-gradient(135deg, var(--dash-primary) 0%, var(--dash-accent) 100%);
    color: #e6fffa;
    border-radius: 18px;
    padding: 22px 24px;
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
  }
  .dash-hero h1, .dash-hero h2, .dash-hero h3 { margin: 0; font-weight: 700; }
  .dash-hero .crumb { font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.9; margin-bottom: 6px; display: block; }
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
  .dash-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 999px;
    color: #e8fffb;
    font-weight: 600;
    font-size: 13px;
    backdrop-filter: blur(8px);
  }
  .dashboard-page .table-hover tbody tr:hover {
    background: color-mix(in srgb, var(--dash-primary) 6%, #fff 94%);
  }
  .dashboard-page .list-group-item { border-color: var(--dash-border); }
  .dashboard-page .badge.bg-light { color: var(--dash-text); border: 1px solid var(--dash-border); }
</style>
@endonce

