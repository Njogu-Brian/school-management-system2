<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report Cards Bulk Print</title>
  <style>
    @page { margin: 10mm; size: A4 portrait; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; }
    table { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; border-collapse: collapse; width: 100%; }
    th, td, div, span, strong, p, li { font-family: DejaVu Sans, sans-serif; color: #111; }
    .report-card-page { page-break-after: always; }
    .report-card-page:last-child { page-break-after: auto; }
  </style>
</head>
<body>
  @foreach($cards as $card)
    <div class="report-card-page">
      @include('academics.report_cards.partials.core', ['dto' => $card['dto'], 'isPdf' => true])
    </div>
  @endforeach
</body>
</html>
