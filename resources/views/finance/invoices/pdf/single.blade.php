@php
  $school = $branding ?? [];
  $logo   = $school['logoBase64'] ?? null;
  $s      = $invoice->student;
  $paymentRows = $paymentRows ?? [];
  // Ensure brand vars exist (DomPDF may not share scope with included partial)
  $brandPrimary   = $brandPrimary ?? setting('finance_primary_color', '#3a1a59');
  $brandMuted     = $brandMuted ?? setting('finance_muted_color', '#6b7280');
  $brandBodyFont  = $brandBodyFont ?? setting('finance_body_font_size', '13');
  $brandHeadingFont = $brandHeadingFont ?? setting('finance_heading_font_size', '19');
  $brandSmallFont = $brandSmallFont ?? setting('finance_small_font_size', '11');
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
@include('layouts.partials.favicon')
@include('layouts.partials.branding-vars')
<style>
  *{ font-family: DejaVu Sans, sans-serif; }
  body{ 
    font-size: {{ $brandBodyFont }}px; 
    color: {{ setting('finance_text_color', '#111') }};
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

  /* Fixed header/footer for DomPDF - same styling as receipt */
  .header{ position: fixed; top: -105px; left: 0; right: 0; height: 105px; border-bottom: 2px solid {{ $brandPrimary }}; }
  .footer{ position: fixed; bottom: -62px; left: 0; right: 0; height: 62px; color: {{ $brandMuted }}; font-size: {{ $brandSmallFont }}px; }

  .small{ font-size: {{ $brandSmallFont }}px; }
  .muted{ color: {{ $brandMuted }}; }
  .h1{ font-size: {{ $brandHeadingFont }}px; font-weight: 700; margin:0; line-height:1.2; color: {{ $brandPrimary }}; }

  .sep{ border:0; border-top:1px solid {{ $brandPrimary }}; margin:6px 0 0 0; }

  table{ border-collapse: collapse; width:100%; }
  .hdr td{ vertical-align: top; }
  .hdr .logo { width: 100px; }
  .hdr img { height: 80px; display:block; }

  .kv{ margin-top: 6px; table-layout: fixed; }
  .kv th,
  .kv td { padding: 6px 8px; border:1px solid #bbb; }
  .kv th { width: 22%; background:#f5f5f5; text-align:left; white-space:nowrap; color: {{ $brandPrimary }}; }
  .kv td { width: 28%; word-wrap: break-word; }

  .items{ margin-top: 10px; }
  .items th,
  .items td { padding: 7px 8px; border:1px solid #ddd; }
  .items thead th{ background: {{ $brandPrimary }}; color: #fff; }
  .items tbody tr.total-row th{ background:#f5f5f5; color: {{ setting('finance_text_color', '#333') }}; border-top: 2px solid {{ $brandPrimary }}; }
  .right{ text-align: right; }
  .center{ text-align: center; }

  .badge{ padding: 2px 6px; border-radius: 3px; font-size: 10.5px; display:inline-block; }
  .b-active{ background:#c8e6c9; }
  .b-pending{ background:#ffe082; }

  .summary{ margin-top:10px; border:1px solid {{ $brandPrimary }}; padding:10px 12px; background:#fafafa; }
  .summary td{ padding:4px 0; }
  .summary .lbl{ font-weight:600; width:55%; }
  .summary .val{ text-align:right; font-weight:700; }

  .paytbl{ margin-top:10px; font-size: {{ max(9, (int)$brandSmallFont - 1) }}px; }
  .paytbl th, .paytbl td{ border:1px solid #ddd; padding:6px 8px; }
  .paytbl thead th{ background: {{ $brandPrimary }}; color:#fff; }

  .pagenum:before{ content: counter(page) " / " counter(pages); }
  .note-internal{ font-size:9px; color:#555; font-style:italic; }
</style>
</head>
<body>

@php
  $watermarkLogo = $school['logoBase64'] ?? null;
@endphp
@if($watermarkLogo)
<div class="watermark" style="background-image: url('{{ $watermarkLogo }}');"></div>
@endif

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
        <div class="h1">{{ $school['name'] ?? config('app.name','Your School') }}</div>
        <div class="small muted">
          @if(!empty($school['address'])) {{ $school['address'] }} |
          @endif
          @if(!empty($school['email'])) {{ $school['email'] }} |
          @endif
          @if(!empty($school['phone'])) Tel: {{ $school['phone'] }} |
          @endif
          @if(!empty($school['website'])) {{ $school['website'] }} @endif
        </div>
        @if(!empty($invoiceHeader ?? ''))
          <div class="small muted">{!! $invoiceHeader !!}</div>
        @endif
        <hr class="sep">
      </td>
      <td style="width:170px; text-align:right;">
        <div class="small muted">Date: {{ ($printedAt ?? now())->format('Y-m-d') }}</div>
        <div class="small muted">Time: {{ ($printedAt ?? now())->format('H:i') }}</div>
        <div class="small"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
      </td>
    </tr>
  </table>
</div>

{{-- ============ FOOTER ============ --}}
<div class="footer">
  <hr class="sep">
  <table>
    <tr>
      <td>Generated by: <strong>{{ $printedBy ?? 'System' }}</strong></td>
      <td class="center muted">Printed: {{ ($printedAt ?? now())->format('Y-m-d H:i') }}</td>
      <td class="right muted">Page <span class="pagenum"></span></td>
    </tr>
  </table>
  @if(!empty($invoiceFooter ?? ''))
    <div class="muted" style="margin-top:4px;">{!! $invoiceFooter !!}</div>
  @endif
</div>

{{-- ============ BODY ============ --}}
<table class="kv">
  <tr>
    <th>Student name</th>
    <td>{{ $s->full_name ?? 'Unknown' }}</td>
    <th>Admission #</th>
    <td>{{ $s->admission_number ?? '-' }}</td>
  </tr>
  <tr>
    <th>Class</th>
    <td>{{ $s->classroom->name ?? '-' }}</td>
    <th>Stream</th>
    <td>{{ $s->stream->name ?? '-' }}</td>
  </tr>
  <tr>
    <th>Period</th>
    <td>{{ $invoice->year }} / Term {{ $invoice->term }}</td>
    <th>Status</th>
    <td>{{ strtoupper($invoice->status ?? '—') }}</td>
  </tr>
</table>

<table class="items">
  <thead>
    <tr>
      <th style="width:5%">#</th>
      <th>Votehead</th>
      <th style="width:12%" class="right">Charged</th>
      <th style="width:10%" class="right">Discount</th>
      <th style="width:12%" class="right">Paid (allocated)</th>
      <th style="width:12%" class="right">Balance (line)</th>
    </tr>
  </thead>
  <tbody>
    @php
      $activeItems = $invoice->items->filter(fn ($item) => ($item->status ?? 'active') === 'active');
      $sumPaidLines = 0;
      $sumDue = 0;
    @endphp
    @foreach($activeItems as $i => $item)
      @php
        $disc = (float) ($item->discount_amount ?? 0);
        $paidLine = $item->allocations->filter(fn ($a) => $a->payment && !$a->payment->reversed)->sum('amount');
        $net = (float) $item->amount - $disc;
        $dueLine = max(0, round($net - (float) $paidLine, 2));
        $sumPaidLines += (float) $paidLine;
        $sumDue += $dueLine;
        $vhName = $item->custom_votehead_name ?: ($item->votehead->name ?? 'Unknown');
      @endphp
      <tr>
        <td class="center">{{ $i + 1 }}</td>
        <td>{{ $vhName }}</td>
        <td class="right">{{ number_format($item->amount, 2) }}</td>
        <td class="right">{{ $disc > 0 ? number_format($disc, 2) : '—' }}</td>
        <td class="right">{{ number_format($paidLine, 2) }}</td>
        <td class="right">{{ number_format($dueLine, 2) }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <th colspan="2" class="right">Line totals</th>
      <th class="right">{{ number_format($activeItems->sum(fn ($it) => (float) $it->amount), 2) }}</th>
      <th class="right">{{ number_format($activeItems->sum(fn ($it) => (float) ($it->discount_amount ?? 0)), 2) }}</th>
      <th class="right">{{ number_format($sumPaidLines, 2) }}</th>
      <th class="right">{{ number_format($sumDue, 2) }}</th>
    </tr>
  </tbody>
</table>

<table class="summary">
  <tr>
    <td class="lbl">Invoice total (after discounts)</td>
    <td class="val">KES {{ number_format((float) $invoice->total, 2) }}</td>
  </tr>
  <tr>
    <td class="lbl">Total payments allocated to this invoice</td>
    <td class="val">KES {{ number_format((float) $invoice->paid_amount, 2) }}</td>
  </tr>
  <tr>
    <td class="lbl">Current balance due</td>
    <td class="val" style="color:{{ ($invoice->balance ?? 0) > 0 ? '#b00020' : '#1b5e20' }};">KES {{ number_format((float) $invoice->balance, 2) }}</td>
  </tr>
</table>

<div style="margin-top:12px;">
  <strong style="font-size:{{ $brandSmallFont }}px;">Payments applied to this invoice</strong>
  @if(count($paymentRows) > 0)
    <table class="paytbl">
      <thead>
        <tr>
          <th>Date</th>
          <th>Receipt</th>
          <th>Method</th>
          <th class="right">Amount (this invoice)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($paymentRows as $pr)
          <tr>
            <td>{{ $pr['date'] ? \Carbon\Carbon::parse($pr['date'])->format('Y-m-d') : '—' }}</td>
            <td>{{ $pr['receipt'] }}</td>
            <td>{{ $pr['method'] }}@if(!empty($pr['is_internal'])) <span class="note-internal">(balance transfer)</span>@endif</td>
            <td class="right">{{ number_format($pr['amount'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p class="small muted" style="margin:6px 0 0 0;">No payments have been allocated to this invoice yet.</p>
  @endif
</div>

</body>
</html>
