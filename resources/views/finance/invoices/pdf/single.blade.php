<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
@include('layouts.partials.favicon')
@include('finance.invoices.pdf._invoice-styles')
</head>
<body>
<div class="invoice-container">
@include('finance.invoices.pdf._invoice-body', [
    'invoice' => $invoice,
    'paymentRows' => $paymentRows ?? [],
    'priorBalanceLines' => $priorBalanceLines ?? [],
    'priorBalanceTotal' => $priorBalanceTotal ?? 0,
    'pdfBalanceDue' => $pdfBalanceDue ?? ($invoice->balance ?? 0),
    'branding' => $branding ?? [],
    'school' => $branding ?? [],
    'printedBy' => $printedBy ?? 'System',
    'printedAt' => $printedAt ?? now(),
    'invoiceHeader' => $invoiceHeader ?? '',
    'invoiceFooter' => $invoiceFooter ?? '',
    'student_overdue_balance' => $student_overdue_balance ?? 0,
    'student_upcoming_balance' => $student_upcoming_balance ?? 0,
])
</div>
</body>
</html>
