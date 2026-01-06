<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $payment->receipt_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="finance-page">
        <div class="finance-shell py-4">
        <div class="d-flex justify-content-end align-items-center mb-3 no-print gap-2">
            @php
                $family = $payment->student->family ?? null;
                $updateLink = $family->updateLink ?? null;
            @endphp
            @if($family && $updateLink && $updateLink->is_active)
                <div class="d-flex flex-column align-items-end me-2" style="position: relative;">
                    <a href="{{ route('family-update.form', $updateLink->token) }}" 
                       target="_blank" 
                       class="btn btn-primary position-relative"
                       style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                              border: none; 
                              color: white; 
                              font-weight: 600; 
                              padding: 10px 20px; 
                              border-radius: 8px; 
                              box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                              transition: all 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)';">
                        <i class="bi bi-person-gear me-2"></i> Update Profile
                    </a>
                    <small class="text-muted mt-1" style="font-size: 11px; white-space: nowrap; text-align: right;">
                        Update student biodata in the system
                    </small>
                </div>
            @elseif($family && !$updateLink)
                {{-- Show button even if link doesn't exist yet (will be created on next load) --}}
                <div class="d-flex flex-column align-items-end me-2" style="position: relative;">
                    <a href="javascript:void(0)" 
                       onclick="alert('Profile update link is being generated. Please refresh the page in a moment.'); location.reload();"
                       class="btn btn-primary position-relative"
                       style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                              border: none; 
                              color: white; 
                              font-weight: 600; 
                              padding: 10px 20px; 
                              border-radius: 8px; 
                              box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                              transition: all 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)';">
                        <i class="bi bi-person-gear me-2"></i> Update Profile
                    </a>
                    <small class="text-muted mt-1" style="font-size: 11px; white-space: nowrap; text-align: right;">
                        Update student biodata in the system
                    </small>
                </div>
            @endif
            <button onclick="window.print()" class="btn btn-success">
                <i class="bi bi-printer"></i> Print
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

