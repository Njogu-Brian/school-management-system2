<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report - {{ $dto['student']['name'] ?? '' }}</title>
  <style>
    @page { margin: 10mm; size: A4; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; }
    table { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; border-collapse: collapse; width: 100%; }
    th, td, div, span, strong, p, li { font-family: DejaVu Sans, sans-serif; color: #111; }
    svg { overflow: visible; }
  </style>
</head>
<body>
  @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => true])
</body>
</html>
