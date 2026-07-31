@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $brandPrimary }}">
    <title>Report Form - {{ $dto['student']['name'] ?? '' }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --tap-min: 48px;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            background: #eef2f7;
            margin: 0;
            padding: max(10px, env(safe-area-inset-top)) max(10px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(10px, env(safe-area-inset-left));
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }
        .top-bar {
            max-width: 960px;
            margin: 0 auto .75rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
        }
        .btn-action {
            min-height: var(--tap-min);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            font-weight: 600;
            border-radius: .7rem;
            width: 100%;
        }
        .report-shell {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            border-radius: .85rem;
            padding: .75rem;
            box-shadow: 0 4px 16px rgba(15,23,42,.08);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 640px) {
            .report-shell {
                padding: .45rem;
                border-radius: .65rem;
                font-size: .82rem;
            }
            .report-shell table { font-size: .72rem !important; }
        }
        @media (min-width: 768px) {
            body { padding: 16px; }
            .report-shell { padding: 1rem; }
        }
        @media print {
            .top-bar { display: none !important; }
            body { background: #fff; padding: 0; }
            .report-shell { box-shadow: none; border-radius: 0; padding: 0; overflow: visible; }
        }
    </style>
</head>
<body>
<div class="top-bar">
    <a href="{{ $portalUrl }}" class="btn btn-outline-secondary btn-action">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> All Reports
    </a>
    <a href="{{ $pdfUrl }}" class="btn btn-primary btn-action" target="_blank" rel="noopener">
        <i class="bi bi-download" aria-hidden="true"></i> Download PDF
    </a>
</div>
<div class="report-shell">
    @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => false])
</div>
</body>
</html>
