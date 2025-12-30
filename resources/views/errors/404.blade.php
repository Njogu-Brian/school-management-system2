<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | 404</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @php
        $cssUrl = file_exists(public_path('build/manifest.json')) 
            ? mix('css/app.css') 
            : (file_exists(public_path('css/app.css')) ? asset('css/app.css') : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    @endphp
    <link rel="stylesheet" href="{{ $cssUrl }}">
    @include('finance.partials.styles')
    <style>
        body { background: var(--fin-bg, #f5f7fb); color: var(--fin-text, #0f172a); }
        .error-shell { max-width: 720px; margin: 60px auto; padding: 0 18px; }
        .error-card { background: var(--fin-surface, #fff); border-radius: 16px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08); padding: 32px; border: 1px solid var(--fin-border, #e5e7eb); }
        .error-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .error-title { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .error-sub { color: var(--fin-muted, #6b7280); margin: 0 0 8px; }
        .error-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="error-shell finance-animate">
        <div class="finance-hero mb-3 d-flex align-items-center justify-content-between">
            <div>
                <h3 class="mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-emoji-frown"></i> 404 | Page Not Found
                </h3>
                <p class="mb-0">The page you were looking for could not be found.</p>
            </div>
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 48px; width: auto;">
        </div>

        <div class="error-card">
            <div class="error-header">
                <span class="badge bg-danger">404</span>
                <h4 class="error-title">We can’t seem to find that page.</h4>
            </div>
            <p class="error-sub">It might have been moved, deleted, or the link may be incorrect.</p>
            <div class="error-actions">
                <a href="{{ url()->previous() }}" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
                <a href="{{ route('finance.discounts.index') }}" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-house"></i> Finance Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
