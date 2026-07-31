@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
    $student = $invoice->student;
    $balance = (float) ($invoice->balance ?? max(0, ($invoice->total ?? 0) - ($invoice->paid_amount ?? 0)));
    $schoolName = $schoolSettings['school_name'] ?? setting('school_name', config('app.name'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $brandPrimary }}">
    <title>Invoice {{ $invoice->invoice_number }} - {{ $schoolName }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --mpesa-green: {{ $brandMpesaGreen }};
            --page-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a1a2e 100%);
            --tap-min: 48px;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            color: var(--text);
            padding: max(12px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left));
        }
        .wrap { max-width: 480px; margin: 0 auto; width: 100%; }
        .card-panel {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .card-head {
            background: var(--page-bg);
            color: #fff;
            padding: 1.15rem 1rem;
            text-align: center;
        }
        .card-head h1 { font-size: 1.2rem; font-weight: 700; margin: .35rem 0 0; }
        .card-body { padding: 1rem 1rem 1.15rem; }
        .student-block { margin-bottom: 1rem; }
        .student-name { font-weight: 700; font-size: 1.02rem; }
        .student-meta { color: var(--muted); font-size: .84rem; margin-top: .15rem; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .45rem 0;
            font-size: .95rem;
        }
        .summary-row > span:first-child { color: var(--muted); flex: 0 0 auto; }
        .summary-row > span:last-child { text-align: right; font-weight: 600; }
        .summary-row.total {
            border-top: 1px solid #e2e8f0;
            margin-top: .55rem;
            padding-top: .8rem;
            font-weight: 700;
            font-size: 1.08rem;
        }
        .summary-row.total > span:first-child { color: var(--text); font-weight: 700; }
        .btn-portal {
            min-height: var(--tap-min);
            border-radius: .75rem;
            font-weight: 700;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            font-size: .98rem;
        }
        .items-wrap { margin-top: 1rem; }
        .items-wrap .fw-semibold { font-size: .92rem; margin-bottom: .55rem; }
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: .9rem;
        }
        .item-row:last-child { border-bottom: none; }
        .item-label { color: #334155; min-width: 0; word-break: break-word; }
        .item-amt { font-weight: 700; white-space: nowrap; }
        .badge-status {
            border-radius: 999px;
            padding: .28rem .7rem;
            font-size: .78rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-weight: 600;
        }
        .actions { display: grid; gap: .65rem; margin-top: 1.1rem; }
        .footer-note {
            color: rgba(255,255,255,.88);
            font-size: .78rem;
            text-align: center;
            line-height: 1.45;
            padding: 0 .25rem;
        }
        @media (min-width: 480px) {
            body { padding: 20px; }
            .card-body { padding: 1.15rem 1.25rem 1.35rem; }
            .card-head { padding: 1.35rem 1.25rem; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-panel">
        <div class="card-head">
            <div class="small opacity-75">{{ $schoolName }}</div>
            <h1>Invoice</h1>
            <div class="small mt-1 opacity-90">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="card-body">
            @if($student)
                <div class="student-block">
                    <div class="student-name">{{ $student->full_name ?? trim($student->first_name.' '.$student->last_name) }}</div>
                    <div class="student-meta">
                        {{ $student->admission_number ?? '' }}
                        @if($student->classroom?->name) · {{ $student->classroom->name }} @endif
                    </div>
                </div>
            @endif

            <div class="summary-row"><span>Term</span><span>{{ $invoice->term->name ?? '—' }} / {{ $invoice->academicYear->year ?? $invoice->year ?? '—' }}</span></div>
            @if($invoice->issued_date)
                <div class="summary-row"><span>Issued</span><span>{{ \Carbon\Carbon::parse($invoice->issued_date)->format('d M Y') }}</span></div>
            @endif
            @if($invoice->due_date)
                <div class="summary-row"><span>Due</span><span>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span></div>
            @endif
            <div class="summary-row"><span>Status</span><span><span class="badge-status">{{ ucfirst($invoice->status ?? 'unpaid') }}</span></span></div>
            <div class="summary-row total"><span>Balance due</span><span class="text-danger">KES {{ number_format($balance, 2) }}</span></div>

            @if($invoice->items && $invoice->items->count())
                <div class="items-wrap">
                    <div class="fw-semibold">Invoice items</div>
                    @foreach($invoice->items as $item)
                        <div class="item-row">
                            <span class="item-label">{{ $item->votehead->name ?? $item->description ?? 'Fee item' }}</span>
                            <span class="item-amt">KES {{ number_format((float) ($item->amount ?? 0), 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="actions">
                @if(!empty($paymentUrl) && $balance > 0)
                    <a href="{{ $paymentUrl }}" class="btn btn-success btn-portal">
                        <i class="bi bi-phone" aria-hidden="true"></i> Pay with M-PESA
                    </a>
                @endif
                @if(!empty($reportPortalUrl))
                    <a href="{{ $reportPortalUrl }}" class="btn btn-outline-primary btn-portal">
                        <i class="bi bi-journal-text" aria-hidden="true"></i> View Report Forms
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="footer-note">
        @if(setting('school_phone')) Tel: {{ setting('school_phone') }} · @endif
        @if(setting('school_email')) {{ setting('school_email') }} @endif
    </div>
</div>
</body>
</html>
