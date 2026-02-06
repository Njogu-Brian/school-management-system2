<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $displayReceiptNumber = $payment->shared_receipt_number ?? $payment->receipt_number;
        $brandPrimary = setting('finance_primary_color', '#3a1a59');
        $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    @endphp
    <title>Receipt - {{ $displayReceiptNumber }}</title>
    @include('layouts.partials.favicon')
    <style>:root { --brand-primary: {{ $brandPrimary }}; --brand-accent: {{ $brandSecondary }}; }</style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @include('finance.partials.styles')
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        .receipt-card { 
            border-radius: 16px; 
            box-shadow: 0 12px 30px rgba(15,23,42,0.1); 
            border: 1px solid var(--fin-border, #e5e7eb);
            max-width: 800px;
            margin: 0 auto;
        }
        .finance-shell {
            padding: 20px !important;
        }
        .finance-card-body {
            padding: 20px !important;
        }
        
        /* Update Profile Button - Smaller size */
        .btn-update-profile {
            background: linear-gradient(135deg, {{ $brandPrimary }} 0%, {{ $brandSecondary }} 100%) !important;
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
            .finance-shell {
                padding: 10px !important;
            }
            .finance-card-body {
                padding: 15px !important;
            }
        }
    </style>
</head>
<body>
    <div class="finance-page">
        <div class="finance-shell py-4">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <h3 class="mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-receipt"></i> Receipt: {{ $displayReceiptNumber }}
                </h3>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if(($totalOutstandingBalance ?? 0) > 0 && $payment->student)
                        <a href="{{ route('receipts.pay-now', $payment->public_token) }}" class="btn btn-success">
                            <i class="bi bi-phone me-1"></i> Pay Now (KES {{ number_format($totalOutstandingBalance ?? 0, 2) }})
                        </a>
                    @endif
                    @if(optional(optional($payment->student)->family)->updateLink?->is_active)
                        <a href="{{ route('family-update.form', optional(optional($payment->student)->family)->updateLink?->token) }}" 
                           target="_blank" 
                           class="btn btn-update-profile">
                            <i class="bi bi-person-gear me-1"></i> Update Profile
                        </a>
                    @endif
                    <a href="{{ route('finance.payments.receipt.pdf', $payment) }}" class="btn btn-finance btn-finance-primary" download>
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-finance btn-finance-outline">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-finance btn-finance-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="finance-card finance-animate receipt-card">
                <div class="finance-card-body p-4">
                    @include('finance.receipts.pdf.template', [
                        'payment' => $payment,
                        'school' => $schoolSettings ?? [],
                        'branding' => $branding ?? [],
                        'receipt_number' => $displayReceiptNumber,
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

