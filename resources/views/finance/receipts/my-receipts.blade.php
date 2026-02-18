@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>My Receipts - {{ $schoolSettings['name'] ?? 'School' }}</title>
    @include('layouts.partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @media print { .no-print { display: none !important; } }
        body { background-color: #f8f9fa; font-size: 16px; -webkit-text-size-adjust: 100%; }
        .btn-update-profile {
            background: linear-gradient(135deg, {{ $brandPrimary }} 0%, {{ $brandSecondary }} 100%) !important;
            border: none !important; color: white !important; font-weight: 500 !important;
            padding: 6px 12px !important; font-size: 13px !important; border-radius: 6px !important;
        }
        .receipt-card { transition: box-shadow 0.2s; }
        .receipt-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <div class="finance-page">
        <div class="finance-shell py-4">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
                <h5 class="mb-0 text-muted">
                    <i class="bi bi-receipt-cutoff me-1"></i> Receipts for {{ $family->students->count() > 1 ? 'your children' : 'your child' }}
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    @if($paymentLinkUrl && ($totalOutstandingBalance ?? 0) > 0)
                        <a href="{{ $paymentLinkUrl }}" class="btn btn-success btn-sm">
                            <i class="bi bi-phone me-1"></i> Pay Now (KES {{ number_format($totalOutstandingBalance ?? 0, 2) }})
                        </a>
                    @endif
                    @if($updateLinkUrl)
                        <a href="{{ $updateLinkUrl }}" target="_blank" class="btn btn-update-profile">
                            <i class="bi bi-person-gear me-1"></i> Update Profile
                        </a>
                    @endif
                </div>
            </div>

            @if($payments->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-receipt display-4"></i>
                        <p class="mt-2 mb-0">No receipts yet. Payments will appear here once made.</p>
                        @if($paymentLinkUrl)
                            <a href="{{ $paymentLinkUrl }}" class="btn btn-primary btn-sm mt-3">Pay fees</a>
                        @endif
                    </div>
                </div>
            @else
                <p class="small text-muted no-print mb-2">Latest receipts first. Click a receipt to view or print it.</p>
                <div class="list-group list-group-flush">
                    @foreach($payments as $payment)
                        <a href="{{ url('/receipt/' . $payment->public_token) }}" target="_blank" class="list-group-item list-group-item-action receipt-card text-decoration-none text-dark py-3 no-print">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <strong>{{ $payment->receipt_number }}</strong>
                                    <span class="text-muted ms-2">– {{ $payment->student->first_name ?? '' }} {{ $payment->student->last_name ?? '' }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark">KES {{ number_format($payment->amount, 2) }}</span>
                                    <span class="text-muted small">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '' }}</span>
                                    <i class="bi bi-box-arrow-up-right small"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
