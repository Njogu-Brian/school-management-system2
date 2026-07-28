<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Report - {{ $dto['student']['name'] ?? '' }}</title>
  <style>
    @page { margin: 12mm 10mm; size: A4; }
    body { font-family: 'DejaVu Sans'; font-size: 12px; color: #000000; margin: 0; }
    table { font-family: 'DejaVu Sans'; font-size: 12px; color: #000000; border-collapse: collapse; width: 100%; }
    th, td, div, span, strong, p, li { font-family: 'DejaVu Sans'; color: #000000; }
  </style>
</head>
<body style="font-family:'DejaVu Sans'; color:#000000; font-size:12px;">
  @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => true])
</body>
</html>
