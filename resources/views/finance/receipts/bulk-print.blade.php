@php
    $branding = $branding ?? [];
    $school = $school ?? [];
    
    // Get logo - prefer branding logoBase64, fallback to school logo, then try to build from filename
    $logo = $branding['logoBase64'] ?? null;
    if (!$logo && !empty($school['logo'])) {
        $logoFile = $school['logo'];
        $logoPath = public_path('images/' . $logoFile);
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    
    $schoolName = $branding['name'] ?? ($school['name'] ?? config('app.name', 'School'));
    $schoolAddress = $branding['address'] ?? ($school['address'] ?? '');
    $schoolPhone = $branding['phone'] ?? ($school['phone'] ?? '');
    $schoolEmail = $branding['email'] ?? ($school['email'] ?? '');
    $schoolWebsite = $branding['website'] ?? '';
    
    $receiptHeader = $receiptHeader ?? \App\Models\Setting::get('receipt_header', '');
    $receiptFooter = $receiptFooter ?? \App\Models\Setting::get('receipt_footer', '');
    
    // Convert amount to words function
    function numberToWords($number) {
        $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 
                 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
        
        $number = (int)$number;
        if ($number == 0) return 'ZERO';
        
        $result = '';
        
        if ($number >= 1000000) {
            $millions = (int)($number / 1000000);
            $result .= numberToWords($millions) . ' MILLION ';
            $number %= 1000000;
        }
        
        if ($number >= 1000) {
            $thousands = (int)($number / 1000);
            if ($thousands < 20) {
                $result .= $ones[$thousands] . ' THOUSAND ';
            } else {
                $result .= $tens[(int)($thousands / 10)] . ' ' . $ones[$thousands % 10] . ' THOUSAND ';
            }
            $number %= 1000;
        }
        
        if ($number >= 100) {
            $result .= $ones[(int)($number / 100)] . ' HUNDRED ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $result .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }
        
        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }
        
        return trim($result) . ' SHILLINGS ONLY';
    }
    
    $printedAt = now();
    $printedBy = optional(auth()->user())->name ?? 'System';
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Bulk Receipts Print</title>
<style>
  *{ font-family: DejaVu Sans, sans-serif; }
  body{ 
    font-size: 11.5px; 
    color:#111;
    position: relative;
  }
  @page { 
    size: A4;
    margin: 135px 24px 80px 24px; /* top, right, bottom, left */
  }
  
  .watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 350px;
    height: 350px;
    min-width: 350px;
    min-height: 350px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center center;
    opacity: 0.2;
    z-index: 0;
    pointer-events: none;
  }

  /* Fixed header/footer for DomPDF */
  .header{ position: fixed; top: -105px; left: 0; right: 0; height: 105px; }
  .footer{ position: fixed; bottom: -62px; left: 0; right: 0; height: 62px; color:#666; font-size: 10px; }

  .small{ font-size: 10.5px; }
  .muted{ color:#666; }
  .h1{ font-size: 20px; font-weight: 700; margin:0; line-height:1.2; }

  .sep{ border:0; border-top:1px solid #ccc; margin:6px 0 0 0; }

  table{ border-collapse: collapse; width:100%; }
  .hdr td{ vertical-align: top; }
  .hdr .logo { width: 100px; }
  .hdr img { height: 80px; display:block; }

  .kv{ margin-top: 6px; table-layout: fixed; }
  .kv th,
  .kv td { padding: 6px 8px; border:1px solid #bbb; }
  .kv th { width: 22%; background:#f5f5f5; text-align:left; white-space:nowrap; }
  .kv td { width: 28%; word-wrap: break-word; }

  .summary{ margin-top: 10px; }
  .summary th,
  .summary td { padding: 7px 8px; border:1px solid #999; }
  .summary th{ background:#f2f2f2; }
  .right{ text-align: right; }
  .center{ text-align: center; }

  .pagenum:before{ content: counter(page) " / " counter(pages); }
  
  .amount-words {
    margin: 15px 0;
    font-size: 10.5px;
    padding: 8px;
    background: #f9f9f9;
  }
  
  .payment-mode {
    margin: 10px 0;
    font-size: 10.5px;
  }
  
  .with-thanks {
    margin: 15px 0;
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
    font-size: 10.5px;
  }
  
  /* Page break for each receipt */
  .receipt-page {
    page-break-after: always;
  }
  
  .receipt-page:last-child {
    page-break-after: auto;
  }
</style>
</head>
<body>

@php
  $watermarkLogo = $logo ?? $branding['logoBase64'] ?? null;
@endphp
@if($watermarkLogo)
<div class="watermark" style="background-image: url('{!! $watermarkLogo !!}');"></div>
@endif

@foreach($receipts as $index => $receiptData)
@php
    $payment = $receiptData['payment'];
    $student = $receiptData['student'];
    $currentFeeBalance = $receiptData['total_outstanding_balance'] ?? 0;
    $amountReceived = $payment->amount ?? 0;
    $balanceCarriedForward = $receiptData['current_outstanding_balance'] ?? ($currentFeeBalance - $amountReceived);
    $amountInWords = numberToWords($amountReceived);
@endphp

<div class="receipt-page">
{{-- ============ HEADER ============ --}}
<div class="header">
  <table class="hdr">
    <tr>
      <td class="logo">
        @if($logo)
          <img src="{{ $logo }}" alt="Logo">
        @endif
      </td>
      <td>
        <div class="h1">{{ $schoolName }}</div>
        <div class="small muted">
          @if(!empty($schoolAddress)) {{ $schoolAddress }} |
          @endif
          @if(!empty($schoolEmail)) {{ $schoolEmail }} |
          @endif
          @if(!empty($schoolPhone)) Tel: {{ $schoolPhone }} |
          @endif
          @if(!empty($schoolWebsite)) {{ $schoolWebsite }} @endif
        </div>
        @if(!empty($receiptHeader))
          <div class="small muted">{!! $receiptHeader !!}</div>
        @endif
        <hr class="sep">
      </td>
      <td style="width:170px; text-align:right;">
        <div class="small muted">Date: {{ $printedAt->format('Y-m-d') }}</div>
        <div class="small muted">Time: {{ $printedAt->format('H:i') }}</div>
        <div class="small"><strong>Receipt #:</strong> {{ $payment->receipt_number }}</div>
      </td>
    </tr>
  </table>
</div>

{{-- ============ FOOTER ============ --}}
<div class="footer">
  <hr class="sep">
  <table>
    <tr>
      <td>Generated by: <strong>{{ $printedBy }}</strong></td>
      <td class="center muted">Printed: {{ $printedAt->format('Y-m-d H:i') }}</td>
      <td class="right muted">Page <span class="pagenum"></span></td>
    </tr>
  </table>
  @if(!empty($receiptFooter))
    <div class="muted" style="margin-top:4px;">{!! $receiptFooter !!}</div>
  @endif
</div>

{{-- ============ BODY ============ --}}
<table class="kv">
  <tr>
    <th>Student</th>
    <td>{{ $student->full_name ?? 'Unknown' }} (Adm: {{ $student->admission_number ?? '-' }})</td>
    <th>Class / Stream</th>
    <td>{{ $student->classroom->name ?? '-' }} / {{ $student->stream->name ?? '-' }}</td>
  </tr>
  <tr>
    <th>System Entry Date</th>
    <td>{{ $payment->created_at->format('d-M-Y') }}</td>
    <th>Payment Date</th>
    <td>{{ $payment->payment_date->format('d M Y') }}</td>
  </tr>
</table>

<table class="summary">
  <thead>
    <tr>
      <th></th>
      <th style="width:16%" class="right">KSHS.</th>
      <th style="width:16%" class="right">CTS</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>Current Fee Balance</th>
      <td class="right">{{ number_format($currentFeeBalance, 2, '.', ',') }}</td>
      <td class="right">00</td>
    </tr>
    <tr>
      <th>Amount Received</th>
      <td class="right">{{ number_format($amountReceived, 2, '.', ',') }}</td>
      <td class="right">00</td>
    </tr>
    <tr>
      <th>Balance c/f</th>
      <td class="right">{{ number_format($balanceCarriedForward, 2, '.', ',') }}</td>
      <td class="right">00</td>
    </tr>
  </tbody>
</table>

<div class="amount-words">
  <strong>Amount in Words</strong> {{ $amountInWords }}
</div>

<div class="payment-mode">
  <strong>Mode of Payment.</strong> {{ strtoupper($payment->paymentMethod->name ?? $payment->payment_method ?? 'CASH') }}
  @if($payment->transaction_code)
    - {{ $payment->transaction_code }}
  @endif
  @if($payment->payment_date)
    ({{ $payment->payment_date->format('d M Y') }})
  @endif
</div>

<div class="with-thanks">
  <strong>With Thanks</strong>
</div>
</div>

@endforeach

</body>
</html>

