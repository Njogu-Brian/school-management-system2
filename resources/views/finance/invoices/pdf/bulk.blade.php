<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoices (Bulk PDF)</title>
@include('finance.invoices.pdf._invoice-styles')
</head>
<body>
@php
  $invoiceBundles = $invoiceBundles ?? collect();
  $lastClassStreamKey = null;
@endphp
@foreach($invoiceBundles as $bundle)
  @php
    $invoice = $bundle['invoice'];
    $s = $invoice->student;
    $className = $invoice->classroom->name ?? $s->classroom->name ?? '—';
    $streamName = trim((string) ($invoice->stream->name ?? $s->stream->name ?? ''));
    $classStreamKey = strtolower(trim((string) $className)).'|'.strtolower($streamName);
    $bannerLabel = $streamName !== '' ? ('Class: '.$className.' — Stream: '.$streamName) : ('Class: '.$className);
    $invoiceHeaderResolved = \App\Services\InvoiceFooterPlaceholderService::replace(
        $invoiceHeader ?? ($invoiceHeaderTemplate ?? ''),
        $invoice
    );
    $invoiceFooterResolved = \App\Services\InvoiceFooterPlaceholderService::replace(
        $invoiceFooterTemplate ?? ($invoiceFooter ?? ''),
        $invoice
    );
  @endphp
  <div class="invoice-container">
    @if($classStreamKey !== $lastClassStreamKey)
      @php $lastClassStreamKey = $classStreamKey; @endphp
      <div class="class-banner">{{ $bannerLabel }}</div>
    @endif
    @include('finance.invoices.pdf._invoice-body', [
        'invoice' => $invoice,
        'paymentRows' => $bundle['payment_rows'] ?? [],
        'priorBalanceLines' => $bundle['prior_balance_lines'] ?? [],
        'priorBalanceTotal' => $bundle['prior_balance_total'] ?? 0,
        'pdfBalanceDue' => $bundle['pdf_balance_due'] ?? ($invoice->balance ?? 0),
        'branding' => $branding ?? [],
        'school' => $branding ?? [],
        'printedBy' => $printedBy ?? 'System',
        'printedAt' => $printedAt ?? now(),
        'invoiceHeader' => $invoiceHeaderResolved,
        'invoiceFooter' => $invoiceFooterResolved,
        'student_overdue_balance' => $bundle['student_overdue_balance'] ?? 0,
        'student_upcoming_balance' => $bundle['student_upcoming_balance'] ?? 0,
    ])
  </div>
@endforeach
</body>
</html>
