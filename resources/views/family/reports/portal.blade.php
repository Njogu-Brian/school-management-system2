@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $termLabel = trim(($link->term?->name ?? '').' / '.($link->academicYear?->year ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $brandPrimary }}">
    <title>Report Cards - {{ $schoolName }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --page-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a1a2e 100%);
            --tap-min: 44px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            color: #1a1a1a;
            padding: 12px;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
        .portal-wrap { max-width: 520px; margin: 0 auto; }
        .portal-header {
            background: #fff;
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
        }
        .portal-header h1 { font-size: 1.2rem; font-weight: 700; margin: .5rem 0 0; }
        .portal-header .sub { color: #666; font-size: .9rem; }
        .child-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
        }
        .child-name { font-size: 1.05rem; font-weight: 700; }
        .child-meta { font-size: .85rem; color: #666; margin-bottom: .75rem; }
        .badge-locked { background: #fff3cd; color: #856404; }
        .badge-open { background: #d1e7dd; color: #0f5132; }
        .invoice-box {
            background: #f8f9fa;
            border-radius: .75rem;
            padding: .75rem;
            margin: .75rem 0;
            font-size: .9rem;
        }
        .invoice-line { display: flex; justify-content: space-between; gap: .5rem; padding: .2rem 0; }
        .btn-portal {
            min-height: var(--tap-min);
            border-radius: .75rem;
            font-weight: 600;
            width: 100%;
        }
        .btn-row { display: grid; gap: .5rem; margin-top: .75rem; }
        @media (min-width: 480px) {
            .btn-row.two { grid-template-columns: 1fr 1fr; }
        }
        .pay-banner {
            background: #fff;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
        }
        .footer-note { color: rgba(255,255,255,.85); font-size: .8rem; text-align: center; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="portal-wrap">
    <div class="portal-header">
        <div class="sub">{{ $schoolName }}</div>
        <h1>Family Report Cards</h1>
        <div class="sub">{{ $termLabel }}</div>
    </div>

    @if($payUrl)
        <div class="pay-banner">
            <div class="mb-2"><i class="bi bi-credit-card"></i> Pay school fees via M-PESA</div>
            <a href="{{ $payUrl }}" class="btn btn-success btn-portal">Pay Now</a>
        </div>
    @endif

    @forelse($children as $child)
        @php
            $student = $child['student'];
            $rc = $child['report_card'];
            $billing = $child['billing'];
            $locked = !($billing['can_view_report'] ?? false);
        @endphp
        <div class="child-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <div>
                    <div class="child-name">{{ $student->full_name ?? trim($student->first_name.' '.$student->last_name) }}</div>
                    <div class="child-meta">
                        {{ $student->admission_number }}
                        @if($rc->classroom?->name) · {{ $rc->classroom->name }} @endif
                        @if($rc->stream?->name) {{ $rc->stream->name }} @endif
                    </div>
                </div>
                <span class="badge rounded-pill {{ $locked ? 'badge-locked' : 'badge-open' }}">
                    {{ $locked ? 'Locked' : 'Available' }}
                </span>
            </div>

            @if($locked)
                <div class="alert alert-warning py-2 mb-2 small mb-0">
                    <i class="bi bi-lock-fill"></i>
                    Clear <strong>KES {{ number_format($billing['report_term_balance'] ?? 0, 2) }}</strong> for this term to view the report card.
                </div>
            @endif

            @if(!empty($billing['invoices']))
                <div class="invoice-box">
                    <div class="fw-semibold mb-1">{{ $billing['display_term_label'] ?? 'Invoices' }}</div>
                    @foreach($billing['invoices'] as $inv)
                        <div class="invoice-line">
                            <span>{{ $inv['invoice_number'] }}</span>
                            <strong>KES {{ number_format($inv['balance'], 2) }}</strong>
                        </div>
                        @foreach($inv['lines'] ?? [] as $line)
                            <div class="invoice-line text-muted" style="font-size:.8rem;padding-left:.5rem;">
                                <span>{{ $line['label'] }}</span>
                                <span>KES {{ number_format($line['balance'], 2) }}</span>
                            </div>
                        @endforeach
                    @endforeach
                    <div class="invoice-line fw-bold border-top pt-2 mt-1">
                        <span>Total due</span>
                        <span>KES {{ number_format($billing['invoice_total_balance'] ?? 0, 2) }}</span>
                    </div>
                </div>
            @elseif(($billing['invoice_scope'] ?? '') === 'none')
                <div class="text-success small"><i class="bi bi-check-circle"></i> Fees up to date</div>
            @endif

            <div class="btn-row {{ $locked ? '' : 'two' }}">
                @unless($locked)
                    <a href="{{ $child['view_url'] }}" class="btn btn-primary btn-portal">
                        <i class="bi bi-eye"></i> View Report
                    </a>
                    <a href="{{ $child['pdf_url'] }}" class="btn btn-outline-primary btn-portal" target="_blank">
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                @else
                    @if($payUrl)
                        <a href="{{ $payUrl }}" class="btn btn-warning btn-portal">
                            <i class="bi bi-credit-card"></i> Pay to Unlock
                        </a>
                    @endif
                @endunless
            </div>
        </div>
    @empty
        <div class="child-card text-center text-muted">
            No published report cards are available yet for this family.
        </div>
    @endforelse

    <div class="footer-note">
        @if($schoolPhone) Tel: {{ $schoolPhone }} · @endif
        @if($schoolEmail) {{ $schoolEmail }} @endif
        <div class="mt-1">Generated {{ now()->format('d M Y, H:i') }}</div>
    </div>
</div>
</body>
</html>
