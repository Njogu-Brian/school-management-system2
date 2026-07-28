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
            --tap-min: 44px;
        }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            color: #1a1a1a;
            padding: 12px;
        }
        .wrap { max-width: 520px; margin: 0 auto; }
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
            padding: 1.25rem;
            text-align: center;
        }
        .card-body { padding: 1.1rem 1.25rem 1.25rem; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .35rem 0;
            font-size: .95rem;
        }
        .summary-row.total {
            border-top: 1px solid #eee;
            margin-top: .5rem;
            padding-top: .75rem;
            font-weight: 700;
            font-size: 1.05rem;
        }
        .btn-portal {
            min-height: var(--tap-min);
            border-radius: .75rem;
            font-weight: 600;
            width: 100%;
        }
        .items-table { width: 100%; font-size: .9rem; border-collapse: collapse; }
        .items-table th, .items-table td { padding: .45rem .35rem; border-bottom: 1px solid #eee; }
        .items-table th { color: #666; font-weight: 600; }
        .badge-status { border-radius: 999px; padding: .25rem .65rem; font-size: .8rem; }
        .footer-note { color: rgba(255,255,255,.85); font-size: .8rem; text-align: center; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-panel">
        <div class="card-head">
            <div class="small opacity-75">{{ $schoolName }}</div>
            <h1 class="h4 mb-0 mt-1">Invoice</h1>
            <div class="small mt-1">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="card-body">
            @if($student)
                <div class="mb-3">
                    <div class="fw-semibold">{{ $student->full_name ?? trim($student->first_name.' '.$student->last_name) }}</div>
                    <div class="text-muted small">
                        {{ $student->admission_number ?? '' }}
                        @if($student->classroom?->name) · {{ $student->classroom->name }} @endif
                    </div>
                </div>
            @endif

            <div class="summary-row"><span>Term</span><span>{{ $invoice->term->name ?? '—' }} / {{ $invoice->academicYear->year ?? $invoice->year ?? '—' }}</span></div>
            @if($invoice->issued_date)
                <div class="summary-row"><span>Issue date</span><span>{{ \Carbon\Carbon::parse($invoice->issued_date)->format('d M Y') }}</span></div>
            @endif
            @if($invoice->due_date)
                <div class="summary-row"><span>Due date</span><span>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span></div>
            @endif
            <div class="summary-row"><span>Status</span><span><span class="badge-status bg-light border">{{ ucfirst($invoice->status ?? 'unpaid') }}</span></span></div>
            <div class="summary-row total"><span>Balance due</span><span class="text-danger">KES {{ number_format($balance, 2) }}</span></div>

            @if($invoice->items && $invoice->items->count())
                <div class="mt-3">
                    <div class="fw-semibold mb-2">Invoice items</div>
                    <table class="items-table">
                        <thead>
                            <tr><th>Item</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->votehead->name ?? $item->description ?? 'Fee item' }}</td>
                                    <td class="text-end">KES {{ number_format((float) ($item->amount ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="d-grid gap-2 mt-3">
                @if(!empty($paymentUrl) && $balance > 0)
                    <a href="{{ $paymentUrl }}" class="btn btn-success btn-portal">
                        <i class="bi bi-phone me-1"></i> Pay with M-PESA
                    </a>
                @endif
                @if(!empty($reportPortalUrl))
                    <a href="{{ $reportPortalUrl }}" class="btn btn-outline-primary btn-portal">
                        <i class="bi bi-journal-text me-1"></i> View Term Report Cards
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
