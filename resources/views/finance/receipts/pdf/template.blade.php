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
        
        @page {
            size: A4;
            margin: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
            background: #fff;
        }
        
        .receipt-container {
            width: 50%;
            max-width: 297mm;
            height: 50vh;
            min-height: 148mm;
            padding: 8px;
            page-break-after: always;
            page-break-inside: avoid;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3a1a59;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        
        .header h1 {
            font-size: 14px;
            color: #3a1a59;
            margin-bottom: 3px;
            font-weight: bold;
        }
        
        .header .school-info {
            font-size: 7px;
            color: #666;
            line-height: 1.3;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #3a1a59;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .receipt-details-table {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .receipt-details-table td {
            padding: 2px 4px;
            border: none;
        }
        
        .receipt-details-table .detail-label {
            font-weight: bold;
            color: #555;
            text-align: left;
            width: 45%;
        }
        
        .receipt-details-table .detail-value {
            color: #333;
            text-align: right;
            width: 55%;
        }
        
        .allocations-table {
            width: 100%;
            margin: 8px 0;
            border-collapse: collapse;
            font-size: 7px;
        }
        
        .allocations-table thead {
            background-color: #3a1a59;
            color: white;
        }
        
        .allocations-table th,
        .allocations-table td {
            padding: 3px 4px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .allocations-table th {
            font-weight: bold;
            font-size: 7px;
        }
        
        .allocations-table td {
            font-size: 7px;
        }
        
        .allocations-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .allocations-table .text-right {
            text-align: right;
        }
        
        .total-section {
            margin-top: 8px;
            padding: 6px;
            background-color: #f5f5f5;
            border: 1px solid #3a1a59;
            border-radius: 3px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 8px;
        }
        
        .total-row.grand-total {
            font-size: 10px;
            font-weight: bold;
            color: #3a1a59;
            border-top: 1px solid #3a1a59;
            padding-top: 4px;
            margin-top: 4px;
        }
        
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        
        .footer .thank-you {
            font-size: 8px;
            font-weight: bold;
            color: #3a1a59;
            margin-bottom: 3px;
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
                $branding = $branding ?? [];
                // Get logo - prefer branding logoBase64, fallback to school logo
                $logo = $branding['logoBase64'] ?? null;
                if (!$logo && !empty($school['logo'])) {
                    $logoFile = $school['logo'];
                    $logoPath = public_path('images/' . $logoFile);
                    if (file_exists($logoPath)) {
                        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                        $mime = $ext === 'svg' ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');
                        $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                    }
                }
            @endphp
            @if($logo)
            <div style="text-align: center; margin-bottom: 3px;">
                <img src="{{ $logo }}" alt="School Logo" style="max-height: 40px; max-width: 120px;">
            </div>
            @endif
            <h1>{{ $school['name'] ?? ($branding['name'] ?? 'SCHOOL NAME') }}</h1>
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
            @if(!empty($receipt_header ?? ''))
            <div class="school-info" style="margin-top: 6px;">{!! $receipt_header !!}</div>
            @endif
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">
            Payment Receipt
        </div>

        <!-- Receipt Details -->
        <table class="receipt-details-table">
            <tr>
                <td class="detail-label">Receipt Number:</td>
                <td class="detail-value"><strong>{{ $receipt_number ?? $payment->receipt_number }}</strong></td>
            </tr>
            <tr>
                <td class="detail-label">Date:</td>
                <td class="detail-value">{{ $date ?? ($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y')) }}</td>
            </tr>
            <tr>
                <td class="detail-label">Student Name:</td>
                <td class="detail-value">{{ $student->first_name ?? 'N/A' }} {{ $student->last_name ?? '' }}</td>
            </tr>
            @if($student->admission_number)
            <tr>
                <td class="detail-label">Admission Number:</td>
                <td class="detail-value">{{ $student->admission_number }}</td>
            </tr>
            @endif
            @if($student->classroom)
            <tr>
                <td class="detail-label">Class:</td>
                <td class="detail-value">{{ $student->classroom->name ?? 'N/A' }}</td>
            </tr>
            @endif
            <tr>
                <td class="detail-label">Payment Method:</td>
                <td class="detail-value">{{ $payment_method ?? 'Cash' }}</td>
            </tr>
            @if($reference ?? $payment->reference)
            <tr>
                <td class="detail-label">Reference:</td>
                <td class="detail-value">{{ $reference ?? $payment->reference }}</td>
            </tr>
            @endif
        </table>

        <!-- Payment Allocations and Unpaid Voteheads -->
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
                            <span style="color: #856404; font-size: 10px;">(Unpaid)</span>
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
                // Get totals for ALL invoices (not just this payment)
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
            <div class="total-row" style="border-top: 1px solid #ddd; padding-top: 3px; margin-top: 3px;">
                <span><strong>Carried Forward:</strong></span>
                <span style="color: #28a745;"><strong>(Ksh {{ number_format($carriedForward, 2) }})</strong></span>
            </div>
            @endif
            @if($hasTotalOutstanding)
            <div class="total-row grand-total" style="border-top: 1px solid #3a1a59; padding-top: 4px; margin-top: 4px; color: #dc3545;">
                <span style="font-size: 10px; font-weight: bold;">Balance:</span>
                <span style="font-size: 10px; font-weight: bold;">Ksh {{ number_format($totalOutstandingBalance, 2) }}</span>
            </div>
            @else
            <div class="total-row grand-total" style="border-top: 1px solid #3a1a59; padding-top: 4px; margin-top: 4px; color: #28a745;">
                <span style="font-size: 10px; font-weight: bold;">Balance:</span>
                <span style="font-size: 10px; font-weight: bold;">Ksh 0.00</span>
            </div>
            @endif
        </div>

        <!-- Narration -->
        @if($narration ?? $payment->narration)
        <div style="margin-top: 6px; padding: 4px; background-color: #f9f9f9; border-left: 2px solid #3a1a59; font-size: 7px;">
            <strong>Narration:</strong> {{ $narration ?? $payment->narration }}
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
            @if(!empty($receipt_footer ?? ''))
                <div style="margin-top: 6px;">{!! $receipt_footer !!}</div>
            @endif
        </div>
    </div>
</body>
</html>

