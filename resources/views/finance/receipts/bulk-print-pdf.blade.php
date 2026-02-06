<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Receipts Print</title>
    @include('layouts.partials.branding-vars')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4 portrait;
            margin: 15mm 10mm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: {{ $brandBodyFont }}px;
            color: {{ setting('finance_text_color', '#333') }};
            background: #fff;
            margin: 0;
            padding: 0;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 190mm;
            padding: 12px 18px;
            page-break-after: always !important;
            page-break-before: always !important;
            page-break-inside: avoid !important;
            margin: 0 auto;
            box-sizing: border-box;
            display: block;
        }
        
        .receipt-container:first-child {
            page-break-before: auto !important;
        }
        
        .receipt-container:last-child {
            page-break-after: auto !important;
        }
        
        /* Force page break for all elements inside receipt */
        .receipt-container > * {
            page-break-inside: avoid;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid {{ $brandPrimary }};
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            font-size: {{ $brandHeadingFont }}px;
            color: {{ $brandPrimary }};
            margin-bottom: 4px;
            font-weight: bold;
        }
        
        .header .school-info {
            font-size: {{ $brandSmallFont }}px;
            color: {{ $brandMuted }};
            line-height: 1.3;
        }
        
        .receipt-title {
            text-align: center;
            font-size: {{ (int)$brandHeadingFont - 3 }}px;
            font-weight: bold;
            color: {{ $brandPrimary }};
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .receipt-details-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            font-size: {{ $brandBodyFont }}px;
        }
        
        .receipt-details-table td {
            padding: 4px 6px;
            border: none;
            line-height: 1.5;
        }
        
        .receipt-details-table .detail-label {
            font-weight: bold;
            color: {{ $brandMuted }};
            text-align: left;
            width: 25%;
        }
        
        .receipt-details-table .detail-value {
            color: {{ setting('finance_text_color', '#333') }};
            text-align: left;
            width: 25%;
        }
        
        .allocations-table {
            width: 100%;
            margin: 12px 0;
            border-collapse: collapse;
            font-size: {{ $brandSmallFont }}px;
        }
        
        .allocations-table thead {
            background-color: {{ $brandPrimary }};
            color: white;
        }
        
        .allocations-table th,
        .allocations-table td {
            padding: 6px 7px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .allocations-table th {
            font-weight: bold;
            font-size: {{ $brandSmallFont }}px;
        }
        
        .allocations-table td {
            font-size: {{ $brandSmallFont }}px;
        }
        
        .allocations-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .allocations-table .text-right {
            text-align: right;
        }
        
        .allocations-table th:nth-child(1),
        .allocations-table td:nth-child(1) {
            width: 3%;
        }
        .allocations-table th:nth-child(2),
        .allocations-table td:nth-child(2) {
            width: 12%;
        }
        .allocations-table th:nth-child(3),
        .allocations-table td:nth-child(3) {
            width: 25%;
        }
        .allocations-table th:nth-child(4),
        .allocations-table td:nth-child(4) {
            width: 12%;
        }
        .allocations-table th:nth-child(5),
        .allocations-table td:nth-child(5) {
            width: 10%;
        }
        .allocations-table th:nth-child(6),
        .allocations-table td:nth-child(6) {
            width: 13%;
        }
        .allocations-table th:nth-child(7),
        .allocations-table td:nth-child(7) {
            width: 15%;
        }
        
        .total-section {
            margin-top: 10px;
            padding: 8px 10px;
            background-color: #f5f5f5;
            border: 1px solid {{ $brandPrimary }};
            border-radius: 3px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: {{ $brandSmallFont }}px;
        }
        
        .total-row.grand-total {
            font-size: 12px;
            font-weight: bold;
            color: {{ $brandPrimary }};
            border-top: 1px solid {{ $brandPrimary }};
            padding-top: 6px;
            margin-top: 6px;
        }
        
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: {{ max(8, (int)$brandSmallFont - 2) }}px;
            color: {{ $brandMuted }};
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        .footer .thank-you {
            font-size: {{ $brandSmallFont }}px;
            font-weight: bold;
            color: {{ $brandPrimary }};
            margin-bottom: 4px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: {{ $brandPrimary }}0d;
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
@foreach($receipts as $index => $receiptData)
    <div class="receipt-container" style="@if($index > 0) page-break-before: always !important; @endif">
        @include('finance.receipts.pdf._receipt-body', $receiptData)
    </div>
@endforeach
</body>
</html>


