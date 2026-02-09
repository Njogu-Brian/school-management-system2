@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMuted = setting('finance_muted_color', '#6b7280');
    $brandBodyFont = setting('finance_body_font_size', '13');
    $brandHeadingFont = setting('finance_heading_font_size', '19');
    $brandSmallFont = setting('finance_small_font_size', '11');
    $branding = $branding ?? [];
    $school = $school ?? [];
    $firstPayment = $receipts[0]['payment'] ?? null;
    $receiptHeader = $receiptHeader ?? \App\Models\Setting::get('receipt_header', '');
    $receiptFooter = $receiptFooter ?? \App\Models\Setting::get('receipt_footer', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Receipts - {{ count($receipts) }} receipt(s)</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        .receipt-container {
            width: 100%;
            max-width: 210mm;
            padding: 12px 18px;
            page-break-after: always;
            page-break-inside: avoid;
            margin: 0 auto;
        }
        .receipt-container:last-child { page-break-after: auto; }
        .header { text-align: center; border-bottom: 2px solid {{ $brandPrimary }}; padding-bottom: 8px; margin-bottom: 10px; }
        .header h1 { font-size: {{ $brandHeadingFont }}px; color: {{ $brandPrimary }}; margin-bottom: 4px; font-weight: bold; }
        .header .school-info { font-size: {{ $brandSmallFont }}px; color: {{ $brandMuted }}; line-height: 1.3; }
        .receipt-title { text-align: center; font-size: {{ (int)$brandHeadingFont - 3 }}px; font-weight: bold; color: {{ $brandPrimary }}; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .receipt-details-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; font-size: {{ $brandBodyFont }}px; }
        .receipt-details-table td { padding: 4px 6px; border: none; line-height: 1.5; }
        .receipt-details-table .detail-label { font-weight: bold; color: {{ $brandMuted }}; text-align: left; width: 25%; }
        .receipt-details-table .detail-value { color: {{ setting('finance_text_color', '#333') }}; text-align: left; width: 25%; }
        .allocations-table { width: 100%; margin: 12px 0; border-collapse: collapse; font-size: {{ $brandSmallFont }}px; }
        .allocations-table thead { background-color: {{ $brandPrimary }}; color: white; }
        .allocations-table th, .allocations-table td { padding: 6px 7px; text-align: left; border: 1px solid #ddd; }
        .allocations-table th { font-weight: bold; font-size: 11px; }
        .allocations-table td { font-size: 11px; }
        .allocations-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .allocations-table .text-right { text-align: right; }
        .allocations-table th:nth-child(1), .allocations-table td:nth-child(1) { width: 3%; }
        .allocations-table th:nth-child(2), .allocations-table td:nth-child(2) { width: 12%; }
        .allocations-table th:nth-child(3), .allocations-table td:nth-child(3) { width: 25%; }
        .allocations-table th:nth-child(4), .allocations-table td:nth-child(4) { width: 12%; }
        .allocations-table th:nth-child(5), .allocations-table td:nth-child(5) { width: 10%; }
        .allocations-table th:nth-child(6), .allocations-table td:nth-child(6) { width: 13%; }
        .allocations-table th:nth-child(7), .allocations-table td:nth-child(7) { width: 15%; }
        .total-section { margin-top: 10px; padding: 8px 10px; background-color: #f5f5f5; border: 1px solid {{ $brandPrimary }}; border-radius: 3px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: {{ $brandSmallFont }}px; }
        .total-row.grand-total { font-size: 12px; font-weight: bold; color: {{ $brandPrimary }}; border-top: 1px solid {{ $brandPrimary }}; padding-top: 6px; margin-top: 6px; }
        .footer { margin-top: 10px; text-align: center; font-size: {{ max(8, (int)$brandSmallFont - 2) }}px; color: {{ $brandMuted }}; border-top: 1px solid #ddd; padding-top: 8px; }
        .footer .thank-you { font-size: {{ $brandSmallFont }}px; font-weight: bold; color: {{ $brandPrimary }}; margin-bottom: 4px; }
        .profile-update-link { margin-top: 8px; padding: 8px 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6; word-break: break-word; font-size: {{ $brandSmallFont }}px; line-height: 1.6; text-align: left; max-width: 100%; }
        .profile-update-link strong { display: block; margin-bottom: 4px; color: {{ $brandPrimary }}; font-size: {{ $brandSmallFont }}px; }
        .profile-update-link a { display: inline-block; color: {{ $brandSecondary }}; text-decoration: none; word-break: break-all; font-size: {{ max(8, (int)$brandSmallFont - 2) }}px; line-height: 1.4; margin-top: 4px; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: {{ $brandBodyFont }}px; color: {{ setting('finance_text_color', '#333') }}; background: #fff; }
        @page { size: A4 portrait; margin: 0; }
    </style>
</head>
<body>
    <div class="no-print bg-light border-bottom py-3 px-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0"><i class="bi bi-receipt-cutoff"></i> {{ count($receipts) }} receipt(s)</h5>
            <div class="d-flex flex-wrap gap-2">
                @if($firstPayment)
                    <a href="{{ route('finance.payments.receipt.pdf', $firstPayment) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    @foreach($receipts as $index => $receiptData)
        @php
            $payment = $receiptData['payment'];
            $student = $receiptData['student'] ?? $payment->student;
            $totalOutstanding = $receiptData['total_outstanding_balance'] ?? 0;
        @endphp
        <div class="no-print mb-2 d-flex flex-wrap gap-2 align-items-center">
            <span class="small text-muted">Receipt {{ $index + 1 }}: {{ $student->full_name ?? 'N/A' }}</span>
            @if($totalOutstanding > 0 && $payment->public_token)
                <a href="{{ route('receipts.pay-now', $payment->public_token) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-phone me-1"></i> Pay Now (KES {{ number_format($totalOutstanding, 2) }})
                </a>
            @endif
            @if(optional(optional($student)->family)->updateLink?->is_active)
                <a href="{{ route('family-update.form', optional(optional($student)->family)->updateLink?->token) }}" target="_blank" class="btn btn-sm" style="background: linear-gradient(135deg, {{ $brandPrimary }} 0%, {{ $brandSecondary }} 100%); color: white;">
                    <i class="bi bi-person-gear me-1"></i> Update Profile
                </a>
            @endif
        </div>
        <div class="receipt-container">
            @include('finance.receipts.pdf._receipt-body', array_merge($receiptData, ['receipt_header' => $receiptData['receipt_header'] ?? $receiptHeader, 'receipt_footer' => $receiptData['receipt_footer'] ?? $receiptFooter]))
        </div>
    @endforeach
</body>
</html>
