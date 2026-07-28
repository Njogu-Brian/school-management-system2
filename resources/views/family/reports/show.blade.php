@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Report Card - {{ $dto['student']['name'] ?? '' }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f3f4f6; margin: 0; padding: 12px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .top-bar {
            max-width: 960px; margin: 0 auto 12px;
            display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: space-between;
        }
        .report-shell {
            max-width: 960px; margin: 0 auto;
            background: #fff; border-radius: .75rem;
            padding: 1rem; box-shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        .btn-action { min-height: 44px; }
    </style>
</head>
<body>
<div class="top-bar">
    <a href="{{ $portalUrl }}" class="btn btn-outline-secondary btn-action">
        <i class="bi bi-arrow-left"></i> All Children
    </a>
    <a href="{{ $pdfUrl }}" class="btn btn-primary btn-action" target="_blank">
        <i class="bi bi-download"></i> Download PDF
    </a>
</div>
<div class="report-shell">
    @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => false])
</div>
</body>
</html>
