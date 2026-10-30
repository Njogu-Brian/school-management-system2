@php
    $brandPrimary = $brandPrimary ?? setting('finance_primary_color', '#3a1a59');
    $brandSuccess = $brandSuccess ?? setting('finance_success_color', '#28a745');
    $brandDanger = $brandDanger ?? setting('finance_danger_color', '#dc3545');
    $brandMuted = $brandMuted ?? setting('finance_muted_color', '#6b7280');
    $brandBodyFont = $brandBodyFont ?? setting('finance_body_font_size', '14');
    $brandHeadingFont = $brandHeadingFont ?? setting('finance_heading_font_size', '20');
    $brandSmallFont = $brandSmallFont ?? setting('finance_small_font_size', '12');
@endphp
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    @page { size: A4 portrait; margin: 0; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: {{ $brandBodyFont }}px;
        color: {{ setting('finance_text_color', '#333') }};
        background: #fff;
    }
    .invoice-container {
        width: 100%;
        max-width: 210mm;
        padding: 12px 18px;
        page-break-after: always;
        page-break-inside: avoid;
        margin: 0 auto;
    }
    .invoice-container:last-child { page-break-after: auto; }
    .header {
        text-align: center;
        border-bottom: 2px solid {{ $brandPrimary }};
        padding-bottom: 8px;
        margin-bottom: 10px;
    }
    .header-school-name, .header h1 {
        font-size: {{ $brandHeadingFont }}px;
        color: {{ $brandPrimary }};
        margin-bottom: 4px;
        font-weight: bold;
    }
    .school-info {
        font-size: {{ $brandBodyFont }}px;
        color: {{ $brandMuted }};
        line-height: 1.4;
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
    .allocations-table th { font-weight: bold; font-size: {{ $brandSmallFont }}px; }
    .allocations-table td { font-size: {{ $brandSmallFont }}px; }
    .allocations-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
    .allocations-table .text-right { text-align: right; }
    .total-section {
        margin-top: 10px;
        padding: 8px 10px;
        background-color: #f5f5f5;
        border: 1px solid {{ $brandPrimary }};
        border-radius: 3px;
    }
    .total-row {
        display: table;
        width: 100%;
        padding: 4px 0;
        font-size: {{ $brandSmallFont }}px;
    }
    .total-row > span:first-child { display: table-cell; text-align: left; }
    .total-row > span:last-child { display: table-cell; text-align: right; }
    .total-row.grand-total {
        font-size: 12px;
        font-weight: bold;
        color: {{ $brandPrimary }};
        border-top: 1px solid {{ $brandPrimary }};
        padding-top: 6px;
        margin-top: 6px;
    }
    .receipt-policy-notice {
        margin-top: 14px;
        padding: 12px 16px;
        background: #fff8e6;
        border: 1px solid #e6b800;
        border-radius: 6px;
        font-size: {{ $brandSmallFont }}px;
        font-weight: 600;
        color: #856404;
        line-height: 1.5;
        text-align: center;
    }
    .footer {
        margin-top: 12px;
        text-align: center;
        font-size: {{ $brandSmallFont }}px;
        color: {{ $brandMuted }};
        border-top: 1px solid #ddd;
        padding-top: 12px;
    }
    .footer .thank-you {
        font-size: {{ $brandSmallFont }}px;
        font-weight: bold;
        color: {{ $brandPrimary }};
        margin-bottom: 4px;
    }
    .footer-meta { font-size: {{ $brandSmallFont }}px; color: {{ $brandMuted }}; margin-bottom: 6px; }
    .footer-details { font-size: {{ $brandSmallFont }}px; color: {{ $brandMuted }}; }
    .footer-sep { margin: 0 6px; opacity: 0.6; }
    .footer-custom {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px dashed #dee2e6;
        font-size: {{ max(8, (int)$brandSmallFont - 2) }}px;
    }
    .class-banner {
        background: #eceaf4;
        border: 1px solid #c4bdd4;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 11px;
        margin: 0 0 8px 0;
        color: #333;
    }
</style>
