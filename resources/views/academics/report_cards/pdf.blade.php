<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report - {{ $dto['student']['name'] ?? '' }}</title>
  <style>
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color:#111; }
    table { font-size: 12px; }
  </style>
</head>
<body>
  @php($isPdf = true)
  @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => $isPdf])
</body>
</html>
