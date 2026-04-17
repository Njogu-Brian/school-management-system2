@php
    $showPrintChrome = $showPrintChrome ?? false;
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandMuted = setting('finance_muted_color', '#6b7280');
    $schoolName = $branding['name'] ?? ($schoolSettings['name'] ?? config('app.name'));
    $logoSrc = $branding['logoBase64'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Payment Agreement — {{ $schoolName }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 12mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 0;
        }
        .no-print {
            padding: 10px 12px;
            background: #f3f4f6;
            border-bottom: 1px solid #ddd;
            margin-bottom: 12px;
            font-family: system-ui, sans-serif;
        }
        .no-print button, .no-print a {
            display: inline-block;
            margin-right: 8px;
            padding: 8px 14px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 6px;
            text-decoration: none;
        }
        .no-print button {
            background: {{ $brandPrimary }};
            color: #fff;
            border: none;
        }
        .no-print a.secondary {
            background: #fff;
            color: {{ $brandPrimary }};
            border: 1px solid {{ $brandPrimary }};
        }
        @media print {
            .no-print { display: none !important; }
        }
        .doc {
            max-width: 190mm;
            margin: 0 auto;
        }
        .letterhead {
            text-align: center;
            border-bottom: 2px solid {{ $brandPrimary }};
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .letterhead img.logo {
            max-height: 52px;
            max-width: 200px;
        }
        .letterhead h1 {
            font-size: 16px;
            margin: 6px 0 4px;
            color: {{ $brandPrimary }};
        }
        .letterhead .meta {
            font-size: 10px;
            color: {{ $brandMuted }};
            line-height: 1.45;
        }
        h2.title {
            text-align: center;
            font-size: 13px;
            letter-spacing: 0.04em;
            margin: 0 0 12px;
            color: {{ $brandPrimary }};
        }
        .section {
            margin-bottom: 10px;
        }
        .section-label {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: {{ $brandMuted }};
            margin-bottom: 4px;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        table.info td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        table.info td.label {
            width: 32%;
            background: #f9fafb;
            font-weight: bold;
        }
        table.schedule {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 6px;
        }
        table.schedule th, table.schedule td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
        }
        table.schedule th {
            background: #f3f4f6;
            font-weight: bold;
        }
        table.schedule td.num { text-align: center; width: 36px; }
        table.schedule td.amt { text-align: right; }
        .intro {
            font-size: 10.5px;
            line-height: 1.5;
            text-align: justify;
            margin: 10px 0;
        }
        .signatures {
            margin-top: 28px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            padding: 8px 12px 0 0;
            font-size: 10.5px;
        }
        .line {
            border-bottom: 1px solid #333;
            min-height: 28px;
            margin-top: 36px;
            margin-bottom: 4px;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 9px;
            color: {{ $brandMuted }};
        }
    </style>
</head>
<body>
@if($showPrintChrome)
<div class="no-print">
    <button type="button" onclick="window.print()"><strong>Print</strong></button>
    <a href="{{ route('finance.fee-payment-plans.download-pdf', $plan) }}" class="secondary">Download PDF</a>
</div>
@endif

<div class="doc">
    <div class="letterhead">
        @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="">
        @endif
        <h1>{{ $schoolName }}</h1>
        <div class="meta">
            @if(!empty($schoolSettings['address'])){!! nl2br(e($schoolSettings['address'])) !!}<br>@endif
            @if(!empty($schoolSettings['phone']))Tel: {{ $schoolSettings['phone'] }}@endif
            @if(!empty($schoolSettings['email'])) &nbsp;·&nbsp; {{ $schoolSettings['email'] }}@endif
            @if(!empty($schoolSettings['registration_number']))<br>Reg: {{ $schoolSettings['registration_number'] }}@endif
        </div>
    </div>

    <h2 class="title">Structured fee payment agreement</h2>

    <div class="section">
        <div class="section-label">Student &amp; payer</div>
        <table class="info">
            <tr>
                <td class="label">Student name</td>
                <td>{{ $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</td>
            </tr>
            <tr>
                <td class="label">Admission No.</td>
                <td>{{ $student->admission_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Class</td>
                <td>{{ $student->classroom->name ?? '—' }}{{ $student->stream ? ' / '.$student->stream->name : '' }}</td>
            </tr>
            <tr>
                <td class="label">Parent / Guardian</td>
                <td>{{ $parent_display_name ?: '________________________________' }}</td>
            </tr>
            @if($plan->invoice)
            <tr>
                <td class="label">Invoice reference</td>
                <td>{{ $plan->invoice->invoice_number ?? 'Inv #'.$plan->invoice->id }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-label">Plan summary</div>
        <table class="info">
            <tr>
                <td class="label">Total agreed amount (KES)</td>
                <td>{{ number_format((float) $plan->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Number of installments</td>
                <td>{{ $plan->installment_count }}</td>
            </tr>
            <tr>
                <td class="label">Plan period</td>
                <td>
                    {{ $plan->start_date?->format('d M Y') }} — {{ $plan->end_date?->format('d M Y') }}
                </td>
            </tr>
            @if($plan->final_clearance_deadline)
            <tr>
                <td class="label">Final clearance deadline</td>
                <td>{{ $plan->final_clearance_deadline->format('d M Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-label">Installment schedule</div>
        <table class="schedule">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Due date</th>
                    <th>Amount (KES)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->installments as $row)
                <tr>
                    <td class="num">{{ $row->installment_number }}</td>
                    <td>{{ $row->due_date?->format('d M Y') }}</td>
                    <td class="amt">{{ number_format((float) $row->amount, 2) }}</td>
                    <td>{{ ucfirst($row->status ?? 'pending') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($plan->notes)
    <div class="section">
        <div class="section-label">Notes</div>
        <p style="font-size:10.5px;margin:0;">{{ $plan->notes }}</p>
    </div>
    @endif

    <p class="intro">{!! nl2br(e($agreement_intro)) !!}</p>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <strong>Parent / Guardian</strong>
                    <div class="line"></div>
                    <div>Signature &nbsp;&nbsp;&nbsp; Date: _______________</div>
                </td>
                <td>
                    <strong>For {{ $schoolName }}</strong>
                    <div class="line"></div>
                    <div>
                        Name: {{ $prepared_by_name ?: '________________________' }}<br>
                        Date: {{ $prepared_at->format('d M Y') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <p class="footer-note">
        Document generated {{ $prepared_at->format('d M Y, H:i') }}.
        @if($plan->hashed_id)
            Reference: {{ $plan->hashed_id }}
        @endif
    </p>
</div>
</body>
</html>
