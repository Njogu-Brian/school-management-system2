@php
    $branding = $branding ?? [];
    $logo = $branding['logoBase64'] ?? null;
    $schoolName = $branding['name'] ?? config('app.name', 'School');
    $schoolAddress = $branding['address'] ?? '';
    $schoolPhone = $branding['phone'] ?? '';
    $schoolEmail = $branding['email'] ?? '';
    $schoolWebsite = $branding['website'] ?? '';

    // Branding vars (fallbacks in case partial is not in scope)
    $brandBodyFont = $brandBodyFont ?? setting('finance_body_font_size', '13');
    $brandHeadingFont = $brandHeadingFont ?? setting('finance_heading_font_size', '19');
    $brandSmallFont = $brandSmallFont ?? setting('finance_small_font_size', '11');
    $brandPrimary = $brandPrimary ?? setting('finance_primary_color', '#3a1a59');
    $brandSecondary = $brandSecondary ?? setting('finance_secondary_color', '#14b8a6');
    $brandSuccess = $brandSuccess ?? setting('finance_success_color', '#28a745');
    $brandDanger = $brandDanger ?? setting('finance_danger_color', '#dc3545');
    $brandMuted = $brandMuted ?? setting('finance_muted_color', '#6b7280');
    
    // Calculate running balance
    $runningBalance = ($hasBalanceBroughtForwardInInvoices ?? false) ? 0 : ($balanceBroughtForward ?? 0);
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
    <title>Statement of Accounts - {{ isset($family) ? ($family->guardian_name ?: 'Family Statement') : $student->full_name }}</title>
    @include('layouts.partials.favicon')
    @include('layouts.partials.branding-vars')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: {{ $brandBodyFont }}px;
            color: {{ setting('finance_text_color', '#000') }};
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            padding: 10mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
        }
        
        @media print {
            body {
                width: 210mm;
                min-height: 297mm;
                padding: 10mm;
                margin: 0;
            }
            .no-print { display: none !important; }
            .transactions-table tbody tr:hover { background-color: transparent !important; }
            .transactions-table th { background: {{ $brandPrimary }} !important; }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        
        .header {
            margin-bottom: 24px;
            padding-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid {{ $brandPrimary }};
        }
        
        .header-logo {
            margin-bottom: 10px;
        }
        
        .header-logo img {
            height: 72px;
            max-width: 160px;
            object-fit: contain;
        }
        
        .school-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: {{ $brandPrimary }};
        }
        
        .school-info {
            font-size: 11px;
            line-height: 1.7;
            color: {{ $brandMuted }};
            margin-bottom: 4px;
        }
        
        .header-date {
            font-size: 10px;
            color: {{ $brandMuted }};
            margin-top: 6px;
        }
        
        .statement-title {
            text-align: center;
            font-size: {{ (int)$brandHeadingFont + 2 }}px;
            font-weight: 700;
            margin: 24px 0 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $brandPrimary }};
        }
        
        .student-info {
            margin-bottom: 18px;
            padding: 10px 14px;
            background: #f8fafc;
            border-left: 4px solid {{ $brandPrimary }};
            font-size: 12px;
            font-weight: 600;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 11px;
        }
        
        .transactions-table th,
        .transactions-table td {
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        
        .transactions-table th {
            background-color: {{ $brandPrimary }};
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .transactions-table th.amount-col,
        .transactions-table th.balance-col {
            text-align: right;
        }
        
        .transactions-table tbody tr:not(.section-header):not(.totals-row) {
            background-color: #fff;
        }
        
        .transactions-table tbody tr:nth-child(even):not(.section-header):not(.totals-row) {
            background-color: #fafbfc;
        }
        
        .transactions-table tbody tr:hover:not(.section-header):not(.totals-row) {
            background-color: #f1f5f9;
        }
        
        .transactions-table .date-col {
            width: 11%;
        }
        
        .transactions-table .narration-col {
            width: 44%;
        }
        
        .transactions-table .amount-col {
            width: 13%;
            text-align: right;
        }
        
        .transactions-table .balance-col {
            width: 13%;
            text-align: right;
        }
        
        .transactions-table td.amount-col,
        .transactions-table td.balance-col {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-weight: 500;
        }
        
        .section-header {
            background-color: #e2e8f0;
            font-weight: 700;
            font-size: 11px;
            padding: 10px 8px;
            color: {{ $brandPrimary }};
        }
        
        .totals-row {
            background-color: #e2e8f0 !important;
            font-weight: 700;
            border-top: 2px solid {{ $brandPrimary }};
        }
        
        .summary-section {
            margin-top: 28px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .summary-row:last-of-type {
            margin-bottom: 0;
        }
        
        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: 600;
            font-size: 12px;
        }
        
        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-weight: 500;
        }
        
        .current-balance {
            font-size: {{ (int)$brandHeadingFont }}px;
            font-weight: 700;
            margin-top: 18px;
            padding: 14px 18px;
            background: {{ $brandPrimary }};
            color: #fff;
            text-align: center;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        
        .footer {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: {{ max(9, (int)$brandSmallFont - 1) }}px;
            color: {{ $brandMuted }};
            text-align: center;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: {{ $brandPrimary }};
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(58,26,89,0.3);
        }
        
        .print-btn:hover {
            background: #2a1539;
        }
    </style>
</head>
<body>
    @unless($isPdfExport ?? false)
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 1000;">
        @if(!empty($paymentLinkUrl))
            <a href="{{ $paymentLinkUrl }}" class="print-btn" style="position: static; text-decoration: none;">Pay Now</a>
        @endif
        @if(!empty($updateLinkUrl))
            <a href="{{ $updateLinkUrl }}" class="print-btn" style="position: static; text-decoration: none; background: {{ $brandSecondary }};">Update Profile</a>
        @endif
        <button class="print-btn" onclick="window.print()" style="position: static;">Print</button>
    </div>
    @endunless
    
    <!-- Header (centered) -->
    <div class="header">
        @if($logo)
            <div class="header-logo">
                <img src="{{ $logo }}" alt="Logo">
            </div>
        @endif
        <div class="school-name">{{ $schoolName }}</div>
        <div class="school-info">
            @if($schoolAddress){{ $schoolAddress }}<br>@endif
            @if($schoolPhone)CALL US ON: {{ $schoolPhone }}@endif
            @if($schoolEmail) | EMAIL US ON: {{ $schoolEmail }}@endif
            @if($schoolWebsite)<br>WEBSITE: {{ $schoolWebsite }}@endif
        </div>
        <div class="header-date">{{ now()->format('l, F d, Y') }}</div>
    </div>
    
    <!-- Statement Title -->
    <div class="statement-title">Statement of Accounts</div>
    
    <!-- Student Info -->
    <div class="student-info">
        @if(isset($family))
            Family: {{ strtoupper($family->guardian_name ?: ('FAMILY #' . $family->id)) }}
            @if(isset($students) && $students->isNotEmpty())
                <br>Students:
                {{ $students->map(fn ($item) => $item->full_name . ' (' . $item->admission_number . ')')->implode(', ') }}
            @endif
        @else
            Student: {{ strtoupper($student->full_name) }}({{ $student->admission_number }})
        @endif
    </div>
    
    <!-- Transactions Table -->
    <table class="transactions-table">
        <thead>
            <tr>
                <th class="date-col">Date</th>
                @if($showStudentColumn ?? false)
                    <th>Student</th>
                @endif
                <th class="narration-col">Narration</th>
                <th class="amount-col">Dr Amount</th>
                <th class="amount-col">Cr Amount</th>
                <th class="balance-col">Running Bal.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped as $groupKey => $groupTransactions)
                <tr class="section-header">
                    <td colspan="{{ ($showStudentColumn ?? false) ? 6 : 5 }}">{{ $groupKey }}</td>
                </tr>
                @foreach($groupTransactions as $txn)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($txn['date'])->format('d-M-Y') }}</td>
                        @if($showStudentColumn ?? false)
                            <td>{{ $txn['student_name'] ?? 'N/A' }}</td>
                        @endif
                        <td>{{ $txn['narration'] ?? 'N/A' }}</td>
                        <td class="amount-col">{{ $txn['debit'] > 0 ? number_format($txn['debit'], 2) : '-' }}</td>
                        <td class="amount-col">{{ $txn['credit'] > 0 ? number_format($txn['credit'], 2) : '-' }}</td>
                        <td class="balance-col">{{ number_format($txn['running_balance'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="totals-row">
                <td colspan="{{ ($showStudentColumn ?? false) ? 3 : 2 }}" style="text-align: right;">Totals:</td>
                <td class="amount-col">{{ number_format($totalDebit ?? 0, 2) }}</td>
                <td class="amount-col">{{ number_format($totalCredit ?? 0, 2) }}</td>
                <td class="balance-col">{{ number_format($finalBalance ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Summary -->
    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">{{ ($balanceBroughtForward ?? 0) >= 0 ? 'Balance B/F:' : 'Overpayment B/F:' }}</span>
            <span class="summary-value">{{ number_format(abs((float) ($balanceBroughtForward ?? 0)), 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Invoiced:</span>
            <span class="summary-value">{{ number_format($totalCharges ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Payments:</span>
            <span class="summary-value">{{ number_format($totalPayments ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Discounts:</span>
            <span class="summary-value">{{ number_format($totalDiscounts ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Ledger Totals (Dr / Cr / Balance):</span>
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
