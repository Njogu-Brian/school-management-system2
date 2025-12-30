<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement - {{ $student->full_name }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body{ font-size: 11px; color:#111; margin:0; padding:0; }
        @page { margin: 140px 24px 100px 24px; }

        .header{ position: fixed; top:-120px; left:0; right:0; height:120px; }
        .footer{ position: fixed; bottom:-90px; left:0; right:0; height:90px; color:#444; font-size:10px; }

        table{ border-collapse: collapse; width:100%; }
        .hdr td{ vertical-align: top; }
        .logo-cell{ width:90px; }
        .logo-cell img{ height:72px; }
        .school-name{ font-size: 18px; font-weight:700; }
        .muted{ color:#666; font-size: 10px; }
        .title{ margin: 8px 0 0 0; font-size: 16px; font-weight: 700; text-align:center; }

        .kv{ margin-top: 4px; table-layout: fixed; }
        .kv th, .kv td{ padding:6px 8px; border:1px solid #bbb; font-size:10.5px; }
        .kv th{ background:#f5f5f5; width:22%; text-align:left; white-space:nowrap; }
        .kv td{ width:28%; word-wrap:break-word; }

        .ledger{ margin-top: 10px; }
        .ledger th, .ledger td{ padding:7px 8px; border:1px solid #999; font-size:10.5px; }
        .ledger th{ background:#f2f2f2; }
        .right{ text-align:right; }
        .center{ text-align:center; }
        .bold{ font-weight:700; }
        .pagenum:before{ content: counter(page) " / " counter(pages); }
        .totals-row th, .totals-row td{ background:#fafafa; font-weight:700; }

        .summary{ margin-top:12px; }
        .summary td{ padding:6px 8px; border:1px solid #bbb; font-size:10.5px; }
        .summary .label{ width:30%; background:#f7f7f7; font-weight:700; }

        .footer hr{ border:0; border-top:1px solid #ccc; margin:6px 0; }
    </style>
</head>
<body>
@php
    $branding = $branding ?? [];
    $logo = $branding['logoBase64'] ?? null;

    $rows = collect();
    foreach ($invoices as $invoice) {
        $rows->push([
            'date' => $invoice->created_at,
            'label_date' => optional($invoice->created_at)->format('d-M-Y'),
            'narration' => 'INVOICE - ' . ($invoice->invoice_number ?? 'N/A'),
            'debit' => $invoice->total ?? 0,
            'credit' => 0,
        ]);
    }
    foreach ($payments as $payment) {
        $rows->push([
            'date' => $payment->payment_date,
            'label_date' => optional($payment->payment_date)->format('d-M-Y'),
            'narration' => 'RECEIPT - ' . ($payment->receipt_number ?? 'PAYMENT'),
            'debit' => 0,
            'credit' => $payment->amount ?? 0,
        ]);
    }
    foreach ($discounts as $discount) {
        $rows->push([
            'date' => $discount->created_at,
            'label_date' => optional($discount->created_at)->format('d-M-Y'),
            'narration' => 'DISCOUNT - ' . ($discount->discountTemplate->name ?? 'Discount'),
            'debit' => 0,
            'credit' => $discount->value ?? 0,
        ]);
    }
    $rows = $rows->sortBy('date')->values();

    $running = 0;
    $totalDebit = 0;
    $totalCredit = 0;
@endphp

<div class="header">
    <table class="hdr">
        <tr>
            <td class="logo-cell">
                @if($logo)
                    <img src="{{ $logo }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="school-name">{{ $branding['name'] ?? config('app.name','School') }}</div>
                <div class="muted">
                    @if(!empty($branding['address'])) {{ $branding['address'] }} |
                    @endif
                    @if(!empty($branding['phone'])) Tel: {{ $branding['phone'] }} |
                    @endif
                    @if(!empty($branding['email'])) Email: {{ $branding['email'] }} |
                    @endif
                    @if(!empty($branding['website'])) {{ $branding['website'] }} @endif
                </div>
                @if(!empty($statementHeader))
                    <div class="muted">{!! $statementHeader !!}</div>
                @endif
            </td>
            <td style="width:180px; text-align:right;">
                <div class="muted">Date: {{ ($printedAt ?? now())->format('d-M-Y') }}</div>
                <div class="muted">Time: {{ ($printedAt ?? now())->format('H:i') }}</div>
                <div class="muted">Printed by: {{ $printedBy ?? 'System' }}</div>
            </td>
        </tr>
    </table>
    <div class="title">Statement of Accounts</div>
</div>

<div class="footer">
    <hr>
    <table style="width:100%;">
        <tr>
            <td class="muted">Served by: {{ $branding['name'] ?? config('app.name') }}</td>
            <td class="center muted">Page <span class="pagenum"></span></td>
            <td class="right muted">{{ ($printedAt ?? now())->format('d-M-Y H:i') }}</td>
        </tr>
    </table>
    @if(!empty($statementFooter))
        <div class="muted" style="margin-top:6px;">{!! $statementFooter !!}</div>
    @endif
</div>

<table class="kv">
    <tr>
        <th>Student</th>
        <td>{{ $student->full_name }} ({{ $student->admission_number }})</td>
        <th>Class</th>
        <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
    </tr>
    <tr>
        <th>Academic Year</th>
        <td>{{ $year }} @if($term) / {{ $terms->find($term)->name ?? "Term {$term}" }} @endif</td>
        <th>Statement Date</th>
        <td>{{ ($printedAt ?? now())->format('d-M-Y') }}</td>
    </tr>
</table>

<table class="ledger">
    <thead>
        <tr>
            <th style="width:16%;">Date</th>
            <th>Narration</th>
            <th style="width:17%;" class="right">Dr Amount</th>
            <th style="width:17%;" class="right">Cr Amount</th>
            <th style="width:17%;" class="right">Running Bal.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            @php
                $running += ($row['debit'] ?? 0) - ($row['credit'] ?? 0);
                $totalDebit += $row['debit'] ?? 0;
                $totalCredit += $row['credit'] ?? 0;
            @endphp
            <tr>
                <td>{{ $row['label_date'] }}</td>
                <td>{{ $row['narration'] }}</td>
                <td class="right">{{ $row['debit'] ? number_format($row['debit'],2) : '' }}</td>
                <td class="right">{{ $row['credit'] ? number_format($row['credit'],2) : '' }}</td>
                <td class="right">{{ number_format($running,2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="center muted">No transactions found for this period.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="totals-row">
            <td colspan="2" class="right">Totals</td>
            <td class="right">{{ number_format($totalDebit,2) }}</td>
            <td class="right">{{ number_format($totalCredit,2) }}</td>
            <td class="right">{{ number_format($running,2) }}</td>
        </tr>
    </tfoot>
</table>

@php
    $balance = $totalDebit - $totalCredit;
@endphp
<table class="summary">
    <tr>
        <td class="label">Total Charges</td>
        <td class="right">{{ number_format($totalDebit,2) }}</td>
        <td class="label">Total Payments & Discounts</td>
        <td class="right">{{ number_format($totalCredit,2) }}</td>
    </tr>
    <tr>
        <td class="label">Current Balance</td>
        <td colspan="3" class="right bold">{{ number_format($balance,2) }}</td>
    </tr>
</table>

</body>
</html>
