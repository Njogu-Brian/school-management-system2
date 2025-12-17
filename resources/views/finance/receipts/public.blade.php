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
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-end align-items-center mb-3 no-print">
            <div>
                <button onclick="window.print()" class="btn btn-success">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('finance.receipts.pdf.template', [
                    'payment' => $payment,
                    'school' => $schoolSettings ?? [],
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

