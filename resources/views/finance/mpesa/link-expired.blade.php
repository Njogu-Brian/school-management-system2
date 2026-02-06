<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#007e33">
    <title>Payment Link Unavailable - M-PESA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ \App\Models\Setting::get('finance_primary_color', '#3a1a59') }};
            --brand-secondary: {{ \App\Models\Setting::get('finance_secondary_color', '#14b8a6') }};
            --pay-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            --card-radius: 1rem;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--pay-bg);
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .pay-card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .pay-header {
            background: var(--pay-bg);
            color: #fff;
            padding: 1.5rem 1.25rem;
            text-align: center;
        }
        .pay-header .bi-clock-history { font-size: 2.5rem; opacity: 0.95; }
        .pay-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0.5rem 0 0; }
        .pay-header .school { font-size: 0.9rem; opacity: 0.9; margin-top: 0.25rem; }
        .pay-body { padding: 1.5rem 1.25rem; }
        .expired-icon { font-size: 3rem; color: #6c757d; margin-bottom: 1rem; }
        .expired-message { color: #333; margin-bottom: 1rem; }
        .expired-detail { font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
    <div class="pay-card">
        <div class="pay-header">
            <i class="bi bi-clock-history"></i>
            <h1>Link no longer available</h1>
            <p class="school mb-0">{{ \App\Models\Setting::get('school_name', 'School') }}</p>
        </div>
        <div class="pay-body text-center">
            <div class="expired-icon"><i class="bi bi-link-45deg"></i></div>
            <p class="expired-message mb-2">
                This payment link has expired, been used, or is no longer valid.
            </p>
            <p class="expired-detail">
                If you still need to pay, please contact the school for a new payment link.
            </p>
        </div>
    </div>
</body>
</html>
