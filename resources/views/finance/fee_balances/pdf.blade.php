<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Balance List - {{ $selectedTerm?->name ?? 'Current Term' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 10px;
        }
        .class-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
            page-break-before: always;
        }
        .class-section:first-of-type {
            page-break-before: auto;
        }
        .section-header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .section-header .logo-cell {
            width: 70px;
            vertical-align: top;
            padding-right: 12px;
        }
        .section-header .logo-cell img {
            max-height: 60px;
            max-width: 70px;
            object-fit: contain;
        }
        .section-header .info-cell {
            vertical-align: top;
        }
        .section-header .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .section-header .contact-info {
            font-size: 9px;
            color: #555;
            line-height: 1.4;
        }
        .section-header .report-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
        }
        .class-header {
            background-color: #2563eb;
            color: white;
            padding: 8px 10px;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-end {
            text-align: right;
        }
        .balance-positive {
            color: #dc2626;
            font-weight: bold;
        }
        .balance-zero {
            color: #059669;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
        @media print {
            .class-section {
                page-break-inside: avoid;
                page-break-before: always;
            }
            .class-section:first-of-type {
                page-break-before: auto;
            }
        }
    </style>
</head>
<body>
    @foreach($studentsByStream as $classStream => $students)
    <div class="class-section">
        <div class="section-header">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td class="logo-cell" style="width:70px; vertical-align:top; padding-right:12px;">
                        @if(!empty($logoBase64))
                            <img src="{{ $logoBase64 }}" alt="Logo" style="max-height:60px; max-width:70px; object-fit:contain;">
                        @endif
                    </td>
                    <td class="info-cell" style="vertical-align:top;">
                        @if($branding['school_name'] ?? null)
                            <div class="school-name">{{ $branding['school_name'] }}</div>
                        @endif
                        <div class="contact-info">
                            @php
                                $contactParts = array_filter([
                                    $branding['school_address'] ?? null,
                                    ($branding['school_phone'] ?? null) ? 'Tel: ' . ($branding['school_phone']) : null,
                                    $branding['school_email'] ?? null,
                                ]);
                            @endphp
                            {{ implode(' • ', $contactParts) }}
                        </div>
                        <div class="report-title">Fee Balance List</div>
                        <div class="contact-info">Term: {{ $selectedTerm?->name ?? 'Current Term' }} ({{ optional($selectedTerm?->academicYear)->year ?? now()->year }}) &nbsp;•&nbsp; {{ $generated_at ?? now()->format('F j, Y H:i') }}</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="class-header">
            {{ str_replace(' | ', ' - ', $classStream) }} ({{ $students->count() }} learner{{ $students->count() !== 1 ? 's' : '' }})
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: {{ $includeAmounts ?? true ? '22%' : '30%' }};">Child's Name</th>
                    <th style="width: {{ $includeAmounts ?? true ? '12%' : '18%' }};">Adm No</th>
                    @if($includeAmounts ?? true)
                    <th style="width: 14%;" class="text-end">Fee Balance</th>
                    @endif
                    <th style="width: {{ $includeAmounts ?? true ? '24%' : '26%' }};">Father</th>
                    <th style="width: {{ $includeAmounts ?? true ? '24%' : '26%' }};">Mother</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student['full_name'] }}</td>
                    <td>{{ $student['admission_number'] }}</td>
                    @if($includeAmounts ?? true)
                    <td class="text-end {{ $student['balance'] > 0 ? 'balance-positive' : 'balance-zero' }}">
                        Ksh {{ number_format($student['balance'], 2) }}
                    </td>
                    @endif
                    <td>
                        @if(!empty($student['father_name']) || !empty($student['father_phone']))
                            {{ $student['father_name'] ?? '-' }}<br>
                            <small>{{ $student['father_phone'] ?? '-' }}</small>
                        @elseif(!empty($student['guardian_name']) || !empty($student['guardian_phone']))
                            Guardian: {{ $student['guardian_name'] ?? '-' }}<br>
                            <small>{{ $student['guardian_phone'] ?? '-' }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(!empty($student['mother_name']) || !empty($student['mother_phone']))
                            {{ $student['mother_name'] ?? '-' }}<br>
                            <small>{{ $student['mother_phone'] ?? '-' }}</small>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            @if($includeAmounts ?? true)
            <tfoot>
                <tr style="background-color: #e2e8f0; font-weight: bold;">
                    <td colspan="3" class="text-end">Total Class Fee Balance:</td>
                    <td class="text-end {{ $students->sum('balance') > 0 ? 'balance-positive' : 'balance-zero' }}">
                        Ksh {{ number_format($students->sum('balance'), 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @endforeach

    <div class="footer">
        <p>Generated by {{ config('app.name') }} - {{ $generated_by ?? 'System' }}</p>
    </div>
</body>
</html>
