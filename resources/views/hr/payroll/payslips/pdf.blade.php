<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip {{ $record->payslip_number ?? $record->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h4, h5, h6 { margin: 0 0 8px; }
        .row { width: 100%; overflow: hidden; margin-bottom: 16px; }
        .col-6 { width: 48%; float: left; }
        .col-6.text-end { float: right; text-align: right; }
        .text-muted { color: #666; }
        .text-primary { color: #0d6efd; }
        .small { font-size: 11px; }
        .fw-semibold, strong { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; }
        .text-end { text-align: right; }
        .border-top td { border-top: 1px solid #ccc; padding-top: 8px; }
        hr { border: none; border-top: 1px solid #ddd; margin: 16px 0; }
        .d-flex { width: 100%; overflow: hidden; }
        .d-flex .text-muted { float: left; }
        .d-flex h4 { float: right; margin: 0; }
    </style>
</head>
<body>
    @include('hr.payroll.payslips._body', ['record' => $record, 'forPdf' => true])
</body>
</html>
