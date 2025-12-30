@php
    $branding = $branding ?? [];
    $logo = $branding['logoBase64'] ?? null;
    $schoolName = $branding['name'] ?? ($school['name'] ?? config('app.name', 'School'));
    $schoolAddress = $branding['address'] ?? ($school['address'] ?? '');
    $schoolPhone = $branding['phone'] ?? ($school['phone'] ?? '');
    $schoolEmail = $branding['email'] ?? ($school['email'] ?? '');
    
    $receiptHeader = $receiptHeader ?? \App\Models\Setting::get('receipt_header', '');
    $receiptFooter = $receiptFooter ?? \App\Models\Setting::get('receipt_footer', '');
    
    // Calculate current fee balance (balance before this payment)
    $currentFeeBalance = $total_outstanding_balance ?? 0; // This is balance BEFORE payment
    $amountReceived = $payment->amount ?? 0;
    $balanceCarriedForward = $current_outstanding_balance ?? ($currentFeeBalance - $amountReceived); // Balance AFTER payment
    
    // Convert amount to words
    function numberToWords($number) {
        $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 
                 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
        
        $number = (int)$number;
        if ($number == 0) return 'ZERO';
        
        $result = '';
        
        if ($number >= 1000000) {
            $millions = (int)($number / 1000000);
            $result .= numberToWords($millions) . ' MILLION ';
            $number %= 1000000;
        }
        
        if ($number >= 1000) {
            $thousands = (int)($number / 1000);
            if ($thousands < 20) {
                $result .= $ones[$thousands] . ' THOUSAND ';
            } else {
                $result .= $tens[(int)($thousands / 10)] . ' ' . $ones[$thousands % 10] . ' THOUSAND ';
            }
            $number %= 1000;
        }
        
        if ($number >= 100) {
            $result .= $ones[(int)($number / 100)] . ' HUNDRED ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $result .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }
        
        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }
        
        return trim($result) . ' SHILLINGS ONLY';
    }
    
    $amountInWords = numberToWords($amountReceived);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
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
            background-position: center;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }
        
        body > *:not(.watermark) {
            position: relative;
            z-index: 1;
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
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
        }
        
        .logo-cell {
            width: 100px;
        }
        
        .logo-cell img {
            height: 80px;
            display: block;
        }
        
        .school-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        
        .school-info {
            font-size: 9px;
            line-height: 1.4;
        }
        
        .receipt-number-section {
            text-align: right;
            font-size: 9px;
        }
        
        .receipt-number {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .dates-section {
            font-size: 8px;
            line-height: 1.5;
        }
        
        .student-info {
            margin: 15px 0;
            font-size: 11px;
            font-weight: 600;
        }
        
        .financial-summary {
            margin: 20px 0;
            width: 100%;
            border-collapse: collapse;
        }
        
        .financial-summary th,
        .financial-summary td {
            padding: 10px;
            border: 1px solid #000;
            text-align: right;
            font-size: 11px;
        }
        
        .financial-summary th {
            background-color: #f5f5f5;
            font-weight: 700;
            text-align: left;
            width: 70%;
        }
        
        .financial-summary .amount-cell {
            width: 15%;
        }
        
        .financial-summary .cents-cell {
            width: 15%;
        }
        
        .amount-in-words {
            margin: 15px 0;
            font-size: 10px;
        }
        
        .amount-in-words-label {
            font-weight: 600;
        }
        
        .payment-details {
            margin: 15px 0;
            display: table;
            width: 100%;
        }
        
        .payment-details-row {
            display: table-row;
        }
        
        .payment-details-label {
            display: table-cell;
            width: 40%;
            font-weight: 600;
            font-size: 10px;
        }
        
        .payment-details-value {
            display: table-cell;
            width: 60%;
            font-size: 10px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #000;
            font-size: 9px;
            text-align: center;
            text-transform: uppercase;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
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
        
        .with-thanks {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
            min-height: 60px;
        }
    </style>
</head>
<body>
    @php
        $watermarkLogo = $branding['logoBase64'] ?? null;
    @endphp
    @if($watermarkLogo)
    <div class="watermark" style="background-image: url('{{ $watermarkLogo }}');"></div>
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
                        @if($schoolPhone){{ $schoolPhone }}<br>@endif
                        @if($schoolAddress && preg_match('/\d{5}/', $schoolAddress, $matches))
                            {{ $matches[0] }}@endif
                        @if($schoolEmail){{ $schoolEmail }}@endif
                    </div>
                </td>
                <td class="receipt-number-section">
                    <div class="receipt-number">No. {{ $payment->receipt_number }}</div>
                    <div class="dates-section">
                        <div>System Entry Date: {{ $payment->created_at->format('d-M-Y') }}</div>
                        <div>Payment Date: {{ $payment->payment_date->format('d M Y') }}</div>
                        <div>Print Date: {{ now()->format('d-M-Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    @if(!empty($receiptHeader))
    <div style="margin-bottom: 15px; font-size: 10px;">
        {!! $receiptHeader !!}
    </div>
    @endif
    
    <!-- Student Info -->
    <div class="student-info">
        Student Name: {{ $student->admission_number ?? 'N/A' }} - {{ strtoupper($student->full_name) }} - {{ $student->classroom->name ?? 'N/A' }}
    </div>
    
    <!-- Financial Summary -->
    <table class="financial-summary">
        <thead>
            <tr>
                <th></th>
                <th class="amount-cell">KSHS.</th>
                <th class="cents-cell">CTS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Current Fee Balance</th>
                <td class="amount-cell">{{ number_format($currentFeeBalance, 2, '.', ',') }}</td>
                <td class="cents-cell">00</td>
            </tr>
            <tr>
                <th>Amount Received</th>
                <td class="amount-cell">{{ number_format($amountReceived, 2, '.', ',') }}</td>
                <td class="cents-cell">00</td>
            </tr>
            <tr>
                <th>Balance c/f</th>
                <td class="amount-cell">{{ number_format($balanceCarriedForward, 2, '.', ',') }}</td>
                <td class="cents-cell">00</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Amount in Words -->
    <div class="amount-in-words">
        <span class="amount-in-words-label">Amount in Words</span> {{ $amountInWords }}
    </div>
    
    <!-- Payment Details -->
    <div class="payment-details">
        <div class="payment-details-row">
            <div class="payment-details-label">Mode of Payment.</div>
            <div class="payment-details-value">
                {{ strtoupper($payment->paymentMethod->name ?? $payment->payment_method ?? 'CASH') }}
                @if($payment->transaction_code)
                    - {{ $payment->transaction_code }}
                @endif
                @if($payment->payment_date)
                    ({{ $payment->payment_date->format('d M Y') }})
                @endif
            </div>
        </div>
    </div>
    
    <!-- With Thanks Box -->
    <div class="with-thanks">
        <strong>With Thanks</strong>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        @if(!empty($receiptFooter))
            {!! $receiptFooter !!}
        @else
            <div>SERVED BY: {{ strtoupper($schoolName) }}. MONEY ONCE PAID IS NOT REFUNDABLE.</div>
            <div style="margin-top: 5px;">{{ strtoupper($schoolName) }}</div>
        @endif
    </div>
</body>
</html>
