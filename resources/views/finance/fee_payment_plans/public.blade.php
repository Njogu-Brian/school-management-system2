@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
    $schoolName = $schoolSettings['name'] ?? config('app.name');
    $invoice = $plan->invoice;
    $balance = $invoice ? (float) $invoice->balance : 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Plan – {{ $schoolName }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --mpesa-green: {{ $brandMpesaGreen }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            color: #1a1a1a;
            padding: 1rem;
            padding-bottom: 2rem;
        }
        .plan-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 480px;
            margin: 0 auto;
        }
        .plan-header {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #fff;
            padding: 1.5rem 1.25rem;
            text-align: center;
        }
        .plan-header img { max-height: 56px; max-width: 180px; }
        .plan-header h1 { font-size: 1.25rem; font-weight: 700; margin: 0.5rem 0 0; }
        .plan-header .sub { font-size: 0.9rem; opacity: 0.9; margin-top: 0.25rem; }
        .plan-body { padding: 1.25rem 1.5rem; }
        .balance-box {
            background: #f0f9f4;
            border: 1px solid #c8e6d0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }
        .balance-box .label { font-size: 0.8rem; color: #555; font-weight: 600; }
        .balance-box .value { font-size: 1.5rem; font-weight: 700; color: var(--mpesa-green); }
        .detail-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; }
        .detail-value { font-weight: 600; color: #333; }
        .installments-table { font-size: 0.9rem; margin-top: 1rem; }
        .installments-table th { font-weight: 600; color: #555; }
        .btn-mpesa {
            width: 100%;
            min-height: 52px;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            background: var(--mpesa-green);
            color: #fff;
            margin-top: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(0,126,51,0.35);
        }
        .btn-mpesa:hover { color: #fff; background: #006629; transform: translateY(-1px); }
        .btn-mpesa:disabled, .btn-mpesa.disabled { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body>
    <div class="plan-card">
        <div class="plan-header">
            @if(!empty($schoolSettings['logo']))
                <img src="{{ asset('storage/'.$schoolSettings['logo']) }}" alt="{{ $schoolName }}" onerror="this.style.display='none'">
            @endif
            <h1>{{ $schoolName }}</h1>
            <p class="sub mb-0">Payment Plan</p>
        </div>
        <div class="plan-body">
            <div class="balance-box">
                <div class="label">Outstanding balance</div>
                <div class="value">Ksh {{ number_format($balance, 2) }}</div>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student</span>
                <span class="detail-value">{{ $plan->student->full_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Admission No.</span>
                <span class="detail-value">{{ $plan->student->admission_number ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plan total</span>
                <span class="detail-value">Ksh {{ number_format($plan->total_amount ?? 0, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Installments</span>
                <span class="detail-value">{{ $plan->installment_count ?? 0 }} × Ksh {{ number_format($plan->installment_amount ?? 0, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="badge bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'completed' ? 'info' : 'secondary') }}">
                        {{ ucfirst($plan->status ?? 'N/A') }}
                    </span>
                </span>
            </div>
            @if($plan->installments && $plan->installments->isNotEmpty())
            <div class="installments-table">
                <h6 class="mb-2">Installment schedule</h6>
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due date</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->installments as $inst)
                        <tr>
                            <td>{{ $inst->installment_number }}</td>
                            <td>{{ $inst->due_date ? $inst->due_date->format('d M Y') : '—' }}</td>
                            <td class="text-end">Ksh {{ number_format($inst->amount ?? 0, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $inst->status === 'paid' ? 'success' : ($inst->status === 'overdue' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($inst->status ?? 'pending') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @if($payNowUrl ?? null)
            <a href="{{ $payNowUrl }}" class="btn-mpesa">
                <i class="bi bi-phone"></i> Pay with M-PESA
            </a>
            <p class="text-muted small mt-2 mb-0 text-center">
                Same payment page for all your children. Enter M-PESA number and amount.
            </p>
            @elseif($balance <= 0)
            <p class="text-success text-center mt-3 mb-0">
                <i class="bi bi-check-circle-fill"></i> This plan is fully paid.
            </p>
            @else
            <p class="text-muted small mt-3 mb-0 text-center">
                Payment link is not available for this plan. Please contact the school.
            </p>
            @endif
        </div>
    </div>
</body>
</html>
