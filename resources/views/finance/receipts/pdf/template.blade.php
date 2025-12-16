<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $receipt_number ?? $payment->receipt_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #3a1a59;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #3a1a59;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .header .school-info {
            font-size: 11px;
            color: #666;
            line-height: 1.6;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #3a1a59;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .receipt-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        
        .detail-value {
            color: #333;
            width: 60%;
            text-align: right;
        }
        
        .allocations-table {
            width: 100%;
            margin: 30px 0;
            border-collapse: collapse;
        }
        
        .allocations-table thead {
            background-color: #3a1a59;
            color: white;
        }
        
        .allocations-table th,
        .allocations-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .allocations-table th {
            font-weight: bold;
            font-size: 11px;
        }
        
        .allocations-table td {
            font-size: 11px;
        }
        
        .allocations-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .allocations-table .text-right {
            text-align: right;
        }
        
        .total-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f5f5f5;
            border: 2px solid #3a1a59;
            border-radius: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #3a1a59;
            border-top: 2px solid #3a1a59;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .footer .thank-you {
            font-size: 12px;
            font-weight: bold;
            color: #3a1a59;
            margin-bottom: 10px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(58, 26, 89, 0.05);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            @php
                $school = $school ?? [];
            @endphp
            <h1>{{ $school['name'] ?? 'SCHOOL NAME' }}</h1>
            @if(!empty($school['address'] ?? ''))
            <div class="school-info">
                {{ $school['address'] ?? '' }}<br>
                @if(!empty($school['phone'] ?? ''))Tel: {{ $school['phone'] }} | @endif
                @if(!empty($school['email'] ?? ''))Email: {{ $school['email'] }}@endif
            </div>
            @endif
            @if(!empty($school['registration_number'] ?? ''))
            <div class="school-info" style="margin-top: 5px;">
                Registration No: {{ $school['registration_number'] }}
            </div>
            @endif
        </div>

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
            @if($reference ?? $payment->reference)
            <div class="detail-row">
                <span class="detail-label">Reference Number:</span>
                <span class="detail-value">{{ $reference ?? $payment->reference }}</span>
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
                @foreach($allocations as $index => $allocationData)
                @php
                    $allocation = is_array($allocationData) ? ($allocationData['allocation'] ?? null) : $allocationData;
                    $invoice = is_array($allocationData) ? ($allocationData['invoice'] ?? null) : ($allocation->invoiceItem->invoice ?? null);
                    $votehead = is_array($allocationData) ? ($allocationData['votehead'] ?? null) : ($allocation->invoiceItem->votehead ?? null);
                    $itemAmount = is_array($allocationData) ? ($allocationData['item_amount'] ?? 0) : ($allocation->invoiceItem->amount ?? 0);
                    $discountAmount = is_array($allocationData) ? ($allocationData['discount_amount'] ?? 0) : ($allocation->invoiceItem->discount_amount ?? 0);
                    $allocatedAmount = is_array($allocationData) ? ($allocationData['allocated_amount'] ?? 0) : ($allocation->amount ?? 0);
                    $balanceAfter = is_array($allocationData) ? ($allocationData['balance_after'] ?? 0) : ($allocation->invoiceItem->getBalance() ?? 0);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $invoice->invoice_number ?? 'N/A' }}</td>
                    <td>{{ $votehead->name ?? 'N/A' }}</td>
                    <td class="text-right">Ksh {{ number_format($itemAmount, 2) }}</td>
                    <td class="text-right">@if($discountAmount > 0)Ksh {{ number_format($discountAmount, 2) }}@else-@endif</td>
                    <td class="text-right"><strong>Ksh {{ number_format($allocatedAmount, 2) }}</strong></td>
                    <td class="text-right">Ksh {{ number_format($balanceAfter, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Total Section -->
        <div class="total-section">
            @php
                $totalBalance = $total_balance_before ?? 0;
                $amountPaid = $total_amount ?? $payment->amount;
                $remainingBalance = $total_balance_after ?? 0;
                $overpayment = $amountPaid > $totalBalance ? ($amountPaid - $totalBalance) : 0;
                $hasRemainingBalance = $remainingBalance > 0;
            @endphp
            
            <div class="total-row">
                <span>Total Balance:</span>
                <span>Ksh {{ number_format($totalBalance, 2) }}</span>
            </div>
            <div class="total-row">
                <span>Amount Paid:</span>
                <span><strong>Ksh {{ number_format($amountPaid, 2) }}</strong></span>
            </div>
            @if($overpayment > 0)
            <div class="total-row" style="color: #28a745;">
                <span>Overpayment:</span>
                <span><strong>(Ksh {{ number_format($overpayment, 2) }})</strong></span>
            </div>
            @endif
            @if($hasRemainingBalance)
            <div class="total-row" style="color: #dc3545;">
                <span>Remaining Balance:</span>
                <span><strong>Ksh {{ number_format($remainingBalance, 2) }}</strong></span>
            </div>
            @endif
        </div>

        <!-- Narration -->
        @if($narration ?? $payment->narration)
        <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #3a1a59;">
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
        </div>
    </div>
</body>
</html>

