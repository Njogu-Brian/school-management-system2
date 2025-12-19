<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report - {{ $dto['student']['name'] ?? '' }}</title>
  <style>
    :root {
      --ink: #111;
      --muted: #4b5563;
      --border: #444;
      --soft: #f3f3f3;
    }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: var(--ink); }
    table { font-size: 12px; }
  </style>
</head>
<body>
  @php($isPdf = true)
  @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => $isPdf])
</body>
</html>
