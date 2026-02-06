<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $receipt_number ?? $payment->receipt_number }}</title>
    @include('layouts.partials.favicon')
    @include('layouts.partials.branding-vars')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4 portrait;
            margin: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: {{ $brandBodyFont }}px;
            color: {{ setting('finance_text_color', '#333') }};
            background: #fff;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 210mm;
            padding: 12px 18px;
            page-break-after: always;
            page-break-inside: avoid;
            margin: 0 auto;
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
            font-size: 12px;
        }
        
        .receipt-details-table td {
            padding: 4px 6px;
            border: none;
            line-height: 1.5;
        }
        
        .receipt-details-table .detail-label {
            font-weight: bold;
            color: #555;
            text-align: left;
            width: 25%;
        }
        
        .receipt-details-table .detail-value {
            color: #333;
            text-align: left;
            width: 25%;
        }
        
        .allocations-table {
            width: 100%;
            margin: 12px 0;
            border-collapse: collapse;
            font-size: 11px;
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
        
        /* Compact column widths for allocations table */
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
        
        /* Mobile-friendly profile update link */
        .profile-update-link {
            margin-top: 8px;
            padding: 8px 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            word-break: break-word;
            font-size: {{ $brandSmallFont }}px;
            line-height: 1.6;
            text-align: left;
            max-width: 100%;
        }
        
        .profile-update-link strong {
            display: block;
            margin-bottom: 4px;
            color: {{ $brandPrimary }};
            font-size: {{ $brandSmallFont }}px;
        }
        
        .profile-update-link a {
            display: inline-block;
            color: {{ $brandSecondary }};
            text-decoration: none;
            word-break: break-all;
            font-size: 9px;
            line-height: 1.4;
            margin-top: 4px;
        }
        
        .profile-update-link a:hover {
            text-decoration: underline;
        }
        
        /* Mobile responsive styles */
        @media screen and (max-width: 768px) {
            body {
                font-size: 14px;
            }
            .receipt-container {
                padding: 10px 12px;
                max-width: 100%;
            }
            .header h1 {
                font-size: 18px;
            }
            .receipt-title {
                font-size: 15px;
            }
            .receipt-details-table {
                font-size: 11px;
            }
            .allocations-table {
                font-size: 10px;
            }
            .allocations-table th,
            .allocations-table td {
                padding: 4px 5px;
                font-size: 10px;
            }
            .total-section {
                font-size: 11px;
            }
            .footer {
                font-size: 10px;
            }
            .profile-update-link {
                font-size: 11px;
                padding: 10px;
            }
            .profile-update-link strong {
                font-size: 12px;
            }
            .profile-update-link a {
                font-size: 10px;
            }
        }
        
        @media screen and (max-width: 576px) {
            body {
                font-size: 13px;
            }
            .receipt-container {
                padding: 8px 10px;
            }
            .header h1 {
                font-size: 16px;
            }
            .receipt-title {
                font-size: 14px;
            }
            .receipt-details-table {
                font-size: 10px;
            }
            .receipt-details-table td {
                padding: 3px 4px;
            }
            .allocations-table {
                font-size: 9px;
            }
            .allocations-table th,
            .allocations-table td {
                padding: 3px 4px;
                font-size: 9px;
            }
            .total-section {
                font-size: 10px;
                padding: 6px 8px;
            }
            .footer {
                font-size: 9px;
            }
            .profile-update-link {
                font-size: 10px;
                padding: 8px;
            }
            .profile-update-link strong {
                font-size: 11px;
            }
            .profile-update-link a {
                font-size: 9px;
            }
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
    <div class="receipt-container">
        @include('finance.receipts.pdf._receipt-body')
    </div>
</body>
</html>

