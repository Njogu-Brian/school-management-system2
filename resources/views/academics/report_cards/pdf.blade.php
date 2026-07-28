<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report - {{ $dto['student']['name'] ?? '' }}</title>
  <style>
    @page { margin: 12mm 10mm; size: A4; }
    * { box-sizing: border-box; color: #111111; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111111; margin: 0; }
    table { font-size: 12px; color: #111111; border-collapse: collapse; }
    th, td, div, span, strong, p { color: #111111; }
  </style>
</head>
<body>
  @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => true])
</body>
</html>
