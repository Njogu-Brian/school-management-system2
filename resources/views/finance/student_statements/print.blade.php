@php
    $branding = $branding ?? [];
    $logo = $branding['logoBase64'] ?? null;
    $schoolName = $branding['name'] ?? config('app.name', 'School');
    $schoolAddress = $branding['address'] ?? '';
    $schoolPhone = $branding['phone'] ?? '';
    $schoolEmail = $branding['email'] ?? '';
    $schoolWebsite = $branding['website'] ?? '';
    
    // Calculate running balance
    $runningBalance = 0;
    $transactionsWithBalance = [];
    foreach ($detailedTransactions ?? [] as $txn) {
        $runningBalance += ($txn['debit'] ?? 0) - ($txn['credit'] ?? 0);
        $txn['running_balance'] = $runningBalance;
        $transactionsWithBalance[] = $txn;
    }
    
    // Group by year/term/grade in format: "2024 / SEP - DEC - GRADE 6"
    $grouped = collect($transactionsWithBalance)->groupBy(function($item) use ($year) {
        $termName = $item['term_name'] ?? '';
        $grade = $item['grade'] ?? '';
        $yr = $item['term_year'] ?? $year;
        return $yr . ' / ' . ($termName ? $termName . ' - ' : '') . ($grade ? $grade : '');
    });
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Accounts - {{ $student->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #000;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            padding: 10mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            min-width: 400px;
            min-height: 400px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
            opacity: 0.2;
            z-index: 0;
            pointer-events: none;
        }
        
        body > *:not(.watermark) {
            position: relative;
            z-index: 10;
        }
        
        @media print {
            body {
                width: 210mm;
                min-height: 297mm;
                padding: 10mm;
                margin: 0;
            }
            .no-print { display: none !important; }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
        }
        
        .logo-cell {
            width: 120px;
        }
        
        .logo-cell img {
            height: 100px;
            display: block;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .school-info {
            font-size: 10px;
            line-height: 1.6;
        }
        
        .statement-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .student-info {
            margin-bottom: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .transactions-table th,
        .transactions-table td {
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .transactions-table th {
            background-color: #f5f5f5;
            font-weight: 700;
            text-align: center;
        }
        
        .transactions-table .date-col {
            width: 10%;
        }
        
        .transactions-table .narration-col {
            width: 45%;
        }
        
        .transactions-table .amount-col {
            width: 12%;
            text-align: right;
        }
        
        .transactions-table .balance-col {
            width: 12%;
            text-align: right;
        }
        
        .transactions-table td.amount-col,
        .transactions-table td.balance-col {
            font-family: 'Courier New', monospace;
        }
        
        .section-header {
            background-color: #e8e8e8;
            font-weight: 700;
            font-size: 11px;
            padding: 10px 6px;
        }
        
        .summary-section {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: 600;
        }
        
        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .current-balance {
            font-size: 16px;
            font-weight: 700;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #000;
            text-align: right;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #3a1a59;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .print-btn:hover {
            background: #2a1539;
        }
    </style>
</head>
<body>
    @php
        $watermarkLogo = $branding['logoBase64'] ?? null;
    @endphp
    @if($watermarkLogo)
    <div class="watermark" style="background-image: url('{!! $watermarkLogo !!}');"></div>
    @endif
    
    <button class="print-btn no-print" onclick="window.print()">Print</button>
    
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logo)
                        <img src="{{ $logo }}" alt="Logo">
                    @endif
                </td>
                <td>
                    <div class="school-name">{{ $schoolName }}</div>
                    <div class="school-info">
                        @if($schoolAddress){{ $schoolAddress }}<br>@endif
                        @if($schoolPhone)CALL US ON: {{ $schoolPhone }}@endif
                        @if($schoolEmail) | EMAIL US ON: {{ $schoolEmail }}@endif
                        @if($schoolWebsite)<br>WEBSITE: {{ $schoolWebsite }}@endif
                    </div>
                </td>
                <td style="text-align: right; font-size: 10px;">
                    <div>{{ now()->format('l, F d, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Statement Title -->
    <div class="statement-title">Statement of Accounts</div>
    
    <!-- Student Info -->
    <div class="student-info">
        Student: {{ strtoupper($student->full_name) }}({{ $student->admission_number }})
    </div>
    
    <!-- Transactions Table -->
    <table class="transactions-table">
        <thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="narration-col">Narration</th>
                <th class="amount-col">Dr Amount</th>
                <th class="amount-col">Cr Amount</th>
                <th class="balance-col">Running Bal.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped as $groupKey => $groupTransactions)
                <tr class="section-header">
                    <td colspan="5">{{ $groupKey }}</td>
                </tr>
                @foreach($groupTransactions as $txn)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($txn['date'])->format('d-M-Y') }}</td>
                        <td>{{ $txn['narration'] ?? 'N/A' }}</td>
                        <td class="amount-col">{{ $txn['debit'] > 0 ? number_format($txn['debit'], 2) : '-' }}</td>
                        <td class="amount-col">{{ $txn['credit'] > 0 ? number_format($txn['credit'], 2) : '-' }}</td>
                        <td class="balance-col">{{ number_format($txn['running_balance'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr style="background-color: #f5f5f5; font-weight: 700;">
                <td colspan="2" style="text-align: right;">Totals:</td>
                <td class="amount-col">{{ number_format($totalDebit ?? 0, 2) }}</td>
                <td class="amount-col">{{ number_format($totalCredit ?? 0, 2) }}</td>
                <td class="balance-col">{{ number_format($finalBalance ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Summary -->
    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">Fees as at statement date:</span>
            <span class="summary-value">{{ number_format($totalDebit ?? 0, 2) }}&nbsp;&nbsp;&nbsp;{{ number_format($totalCredit ?? 0, 2) }}&nbsp;&nbsp;&nbsp;{{ number_format($finalBalance ?? 0, 2) }}</span>
        </div>
        <div class="current-balance">
            CURRENT BALANCE: {{ number_format($finalBalance ?? 0, 2) }}
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        @if(!empty($statementFooter))
            {!! $statementFooter !!}
        @else
            <div>SERVED BY: {{ $schoolName }}</div>
            <div style="margin-top: 5px; font-style: italic;">Powered by Mzizi School Management ERP</div>
        @endif
    </div>
</body>
</html>
