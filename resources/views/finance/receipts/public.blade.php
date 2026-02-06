<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Receipt - {{ $payment->receipt_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        body {
            background-color: #f8f9fa;
            font-size: 16px; /* Base font size for mobile readability */
            -webkit-text-size-adjust: 100%;
        }
        
        /* Mobile-first responsive styles */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
            .finance-shell {
                padding: 10px !important;
            }
            .card {
                margin: 0;
                border-radius: 0;
            }
            .card-body {
                padding: 15px !important;
            }
        }
        
        /* Update Profile Button - Smaller size */
        .btn-update-profile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            color: white !important;
            font-weight: 500 !important;
            padding: 6px 12px !important;
            font-size: 13px !important;
            border-radius: 6px !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25) !important;
            transition: all 0.2s ease !important;
            white-space: nowrap;
        }
        
        .btn-update-profile:hover, .btn-update-profile:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35) !important;
        }
        
        .btn-update-profile i {
            font-size: 12px;
        }
        
        @media (max-width: 576px) {
            .btn-update-profile {
                padding: 5px 10px !important;
                font-size: 12px !important;
            }
            .btn-update-profile i {
                font-size: 11px;
            }
        }
        
        /* Mobile-friendly link preview in receipt */
        .profile-update-link {
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            word-break: break-word;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .profile-update-link strong {
            display: block;
            margin-bottom: 6px;
            color: #3a1a59;
            font-size: 14px;
        }
        
        .profile-update-link a {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            word-break: break-all;
            font-size: 12px;
            padding: 4px 0;
        }
        
        .profile-update-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .profile-update-link {
                padding: 8px;
                font-size: 12px;
            }
            .profile-update-link strong {
                font-size: 13px;
            }
            .profile-update-link a {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="finance-page">
        <div class="finance-shell py-4">
        <div class="d-flex justify-content-end align-items-center mb-3 no-print gap-2 flex-wrap">
            @php
                $family = $payment->student->family ?? null;
                $updateLink = $family->updateLink ?? null;
                $hasBalance = ($totalOutstandingBalance ?? 0) > 0;
            @endphp
            @if($hasBalance)
                <a href="{{ route('receipts.pay-now', $payment->public_token) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-phone me-1"></i> Pay Now (KES {{ number_format($totalOutstandingBalance ?? 0, 2) }})
                </a>
            @endif
            @if($family && $updateLink && $updateLink->is_active)
                <a href="{{ route('family-update.form', $updateLink->token) }}" 
                   target="_blank" 
                   class="btn btn-update-profile">
                    <i class="bi bi-person-gear me-1"></i> Update Profile
                </a>
            @else
                {{-- Show button even if family/link doesn't exist yet (will be created on next load) --}}
                <a href="javascript:void(0)" 
                   onclick="location.reload();"
                   class="btn btn-update-profile">
                    <i class="bi bi-person-gear me-1"></i> Update Profile
                </a>
            @endif
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                @include('finance.receipts.pdf.template', [
                    'payment' => $payment,
                    'school' => $schoolSettings ?? [],
                    'branding' => $branding ?? [],
                    'receipt_number' => $payment->receipt_number,
                    'date' => $payment->receipt_date ? \Carbon\Carbon::parse($payment->receipt_date)->format('d/m/Y') : ($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : date('d/m/Y')),
                    'student' => $payment->student,
                    'allocations' => $allocations ?? collect(),
                    'total_amount' => $payment->amount,
                    'total_balance_before' => $totalBalanceBefore ?? 0,
                    'total_balance_after' => $totalBalanceAfter ?? 0,
                    'total_outstanding_balance' => $totalOutstandingBalance ?? 0,
                    'total_invoices' => $totalInvoices ?? 0,
                    'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method ?? 'Cash',
                    'transaction_code' => $payment->transaction_code,
                    'narration' => $payment->narration,
                ])
            </div>
        </div>
    </div>
</body>
</html>

