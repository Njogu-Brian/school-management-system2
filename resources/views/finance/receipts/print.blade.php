@php
    $branding = $branding ?? [];
    $logo = $branding['logoBase64'] ?? null;
    $school = $school ?? [];
    $receiptHeader = $receiptHeader ?? \App\Models\Setting::get('receipt_header', '');
    $receiptFooter = $receiptFooter ?? \App\Models\Setting::get('receipt_footer', '');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #3a1a59;
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
            width: 80px;
        }
        
        .logo-cell img {
            height: 64px;
            display: block;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #3a1a59;
        }
        
        .school-info {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }
        
        .date-cell {
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        
        @if(!empty($receiptHeader))
        .custom-header {
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        @endif
        
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #3a1a59;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .receipt-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            display: table-cell;
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        
        .detail-value {
            display: table-cell;
            color: #333;
            text-align: right;
            width: 60%;
        }
        
        .allocations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }
        
        .allocations-table thead {
            background-color: #3a1a59;
            color: white;
        }
        
        .allocations-table th,
        .allocations-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .allocations-table th {
            font-weight: bold;
        }
        
        .allocations-table .text-right {
            text-align: right;
        }
        
        .total-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border: 2px solid #3a1a59;
            border-radius: 5px;
        }
        
        .total-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            font-size: 11px;
        }
        
        .total-row span:first-child {
            display: table-cell;
            width: 70%;
        }
        
        .total-row span:last-child {
            display: table-cell;
            width: 30%;
            text-align: right;
        }
        
        .grand-total {
            border-top: 2px solid #3a1a59;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .narration-box {
            margin-top: 20px;
            padding: 12px;
            background-color: #f9f9f9;
            border-left: 4px solid #3a1a59;
            font-size: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .thank-you {
            font-size: 12px;
            font-weight: bold;
            color: #3a1a59;
            margin-bottom: 8px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3a1a59;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #2a1539;
        }
    </style>
</head>
<body>
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
                    <div class="school-name">{{ $school['name'] ?? config('app.name', 'Your School') }}</div>
                    <div class="school-info">
                        @if(!empty($school['address'])){{ $school['address'] }} | @endif
                        @if(!empty($school['phone']))CALL US ON: {{ $school['phone'] }} | @endif
                        @if(!empty($school['email']))EMAIL US ON: {{ $school['email'] }} | @endif
                        @if(!empty($school['website']))WEBSITE: {{ $school['website'] }}@endif
                    </div>
                </td>
                <td class="date-cell">
                    <div>{{ now()->format('l, F d, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    @if(!empty($receiptHeader))
    <div class="custom-header">
        {!! $receiptHeader !!}
    </div>
    @endif
    
    <!-- Receipt Title -->
    <div class="receipt-title">
        Payment Receipt
    </div>
    
    <!-- Receipt Details -->
    <div class="receipt-details">
        <div class="detail-row">
            <span class="detail-label">Receipt Number:</span>
            <span class="detail-value"><strong>{{ $receipt_number ?? $payment->receipt_number }}</strong></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date:</span>
            <span class="detail-value">{{ $date ?? ($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y')) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Student Name:</span>
            <span class="detail-value">{{ $student->first_name ?? 'N/A' }} {{ $student->last_name ?? '' }}</span>
        </div>
        @if($student->admission_number)
        <div class="detail-row">
            <span class="detail-label">Admission Number:</span>
            <span class="detail-value">{{ $student->admission_number }}</span>
        </div>
        @endif
        @if($student->classroom)
        <div class="detail-row">
            <span class="detail-label">Class:</span>
            <span class="detail-value">{{ $student->classroom->name ?? 'N/A' }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Payment Method:</span>
            <span class="detail-value">{{ $payment_method ?? 'Cash' }}</span>
        </div>
        @if($transaction_code ?? $payment->transaction_code)
        <div class="detail-row">
            <span class="detail-label">Transaction Code:</span>
            <span class="detail-value">{{ $transaction_code ?? $payment->transaction_code }}</span>
        </div>
        @endif
    </div>
    
    <!-- Payment Allocations -->
    @if(isset($allocations) && $allocations->isNotEmpty())
    <table class="allocations-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice Number</th>
                <th>Votehead</th>
                <th class="text-right">Item Amount</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-right">Balance Remaining</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allocations as $index => $itemData)
            @php
                $type = is_array($itemData) ? ($itemData['type'] ?? 'paid') : 'paid';
                $invoice = is_array($itemData) ? ($itemData['invoice'] ?? null) : null;
                $votehead = is_array($itemData) ? ($itemData['votehead'] ?? null) : null;
                $itemAmount = is_array($itemData) ? ($itemData['item_amount'] ?? 0) : 0;
                $discountAmount = is_array($itemData) ? ($itemData['discount_amount'] ?? 0) : 0;
                $allocatedAmount = is_array($itemData) ? ($itemData['allocated_amount'] ?? 0) : 0;
                $balanceAfter = is_array($itemData) ? ($itemData['balance_after'] ?? 0) : 0;
                $isPaid = $type === 'paid' && $allocatedAmount > 0;
            @endphp
            <tr style="{{ $isPaid ? '' : 'background-color: #fff3cd;' }}">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $invoice->invoice_number ?? 'N/A' }}</td>
                <td>
                    {{ $votehead->name ?? 'N/A' }}
                    @if(!$isPaid)
                        <span style="color: #856404; font-size: 9px;">(Unpaid)</span>
                    @endif
                </td>
                <td class="text-right">Ksh {{ number_format($itemAmount, 2) }}</td>
                <td class="text-right">@if($discountAmount > 0)Ksh {{ number_format($discountAmount, 2) }}@else-@endif</td>
                <td class="text-right">
                    @if($isPaid)
                        <strong>Ksh {{ number_format($allocatedAmount, 2) }}</strong>
                    @else
                        <span style="color: #856404;">-</span>
                    @endif
                </td>
                <td class="text-right">
                    <strong style="{{ $balanceAfter > 0 ? 'color: #dc3545;' : 'color: #28a745;' }}">
                        Ksh {{ number_format($balanceAfter, 2) }}
                    </strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <!-- Total Section -->
    <div class="total-section">
        @php
            $amountPaid = $total_amount ?? $payment->amount;
            $totalOutstandingBalance = $total_outstanding_balance ?? 0;
            $totalInvoices = $total_invoices ?? 0;
            $hasTotalOutstanding = $totalOutstandingBalance > 0;
        @endphp
        
        <div class="total-row">
            <span>Total Invoices:</span>
            <span><strong>Ksh {{ number_format($totalInvoices, 2) }}</strong></span>
        </div>
        <div class="total-row">
            <span>Payment Made:</span>
            <span><strong>Ksh {{ number_format($amountPaid, 2) }}</strong></span>
        </div>
        
        @php
            $carriedForward = $payment->unallocated_amount ?? 0;
        @endphp
        @if($carriedForward > 0)
        <div class="total-row" style="border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;">
            <span><strong>Carried Forward:</strong></span>
            <span style="color: #28a745;"><strong>(Ksh {{ number_format($carriedForward, 2) }})</strong></span>
        </div>
        @endif
        @if($hasTotalOutstanding)
        <div class="total-row grand-total" style="color: #dc3545;">
            <span>Balance:</span>
            <span>Ksh {{ number_format($totalOutstandingBalance, 2) }}</span>
        </div>
        @else
        <div class="total-row grand-total" style="color: #28a745;">
            <span>Balance:</span>
            <span>Ksh 0.00</span>
        </div>
        @endif
    </div>
    
    <!-- Narration -->
    @if($narration ?? $payment->narration)
    <div class="narration-box">
        <strong>Narration:</strong><br>
        {{ $narration ?? $payment->narration }}
    </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <div class="thank-you">Thank You for Your Payment!</div>
        <div>This is a computer-generated receipt. No signature required.</div>
        <div style="margin-top: 10px;">
            Generated on: {{ date('d M Y, H:i:s') }}<br>
            @if(!empty($school['phone'] ?? ''))For inquiries, contact: {{ $school['phone'] }}@endif
        </div>
        @if(!empty($receiptFooter))
            <div style="margin-top: 8px;">{!! $receiptFooter !!}</div>
        @endif
    </div>
</body>
</html>

