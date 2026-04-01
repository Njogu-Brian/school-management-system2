{{-- Shared receipt body: same content for view, print, and PDF. --}}
@php
    $brandPrimary = $brandPrimary ?? setting('finance_primary_color', '#3a1a59');
    $brandSecondary = $brandSecondary ?? setting('finance_secondary_color', '#14b8a6');
    $brandSuccess = $brandSuccess ?? setting('finance_success_color', '#28a745');
    $brandDanger = $brandDanger ?? setting('finance_danger_color', '#dc3545');
    $school = $school ?? [];
    $branding = $branding ?? [];
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
<!-- Header -->
<div class="header">
    @if($logo)
    <div style="text-align: center; margin-bottom: 4px;">
        <img src="{{ $logo }}" alt="School Logo" style="max-height: 50px; max-width: 140px;">
    </div>
    @endif
    <h1 class="header-school-name">{{ $school['name'] ?? ($branding['name'] ?? 'SCHOOL NAME') }}</h1>
    @if(!empty($school['address'] ?? '') || !empty($school['phone'] ?? '') || !empty($school['email'] ?? ''))
    <div class="school-info">
        @if(!empty($school['address'] ?? '')){{ $school['address'] }}<br>@endif
        @if(!empty($school['phone'] ?? ''))Tel: {{ $school['phone'] }}@endif
        @if(!empty($school['phone'] ?? '') && !empty($school['email'] ?? '')) | @endif
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
        <td class="detail-label">Student Name:</td>
        <td class="detail-value">{{ $student->full_name ?? 'N/A' }}</td>
        <td class="detail-label" style="text-align: right; width: 25%;">Receipt Number:</td>
        <td class="detail-value" style="text-align: right; width: 25%;"><strong>{{ $receipt_number ?? $payment->receipt_number }}</strong></td>
    </tr>
    @if($student->admission_number)
    <tr>
        <td class="detail-label">Admission Number:</td>
        <td class="detail-value">{{ $student->admission_number }}</td>
        <td class="detail-label" style="text-align: right; width: 25%;">Date:</td>
        <td class="detail-value" style="text-align: right; width: 25%;">{{ $date ?? ($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y')) }}</td>
    </tr>
    @else
    <tr>
        <td colspan="2"></td>
        <td class="detail-label" style="text-align: right; width: 25%;">Date:</td>
        <td class="detail-value" style="text-align: right; width: 25%;">{{ $date ?? ($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y')) }}</td>
    </tr>
    @endif
    @if($student->classroom)
    <tr>
        <td class="detail-label">Class:</td>
        <td class="detail-value">{{ $student->classroom->name ?? 'N/A' }}</td>
        <td colspan="2"></td>
    </tr>
    @endif
    @if(!empty($receipt_term_label ?? null))
    <tr>
        <td class="detail-label">Term (invoice):</td>
        <td class="detail-value" colspan="3">{{ $receipt_term_label }}</td>
    </tr>
    @endif
    @if(!empty($invoice_numbers_summary ?? null))
    <tr>
        <td class="detail-label">Invoice number(s):</td>
        <td class="detail-value" colspan="3"><strong>{{ $invoice_numbers_summary }}</strong></td>
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
            <th>Term</th>
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
            <td style="font-size: 8px;">{{ optional($invoice->term)->name ?? '—' }}</td>
            <td>
                {{ $votehead->name ?? 'N/A' }}
                @if(!$isPaid)
                    <span style="color: #856404; font-size: 8px;">(Unpaid)</span>
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
        $balanceAfterReceiptLines = $total_balance_after ?? null;
        $receiptBalance = $balanceAfterReceiptLines !== null ? (float) $balanceAfterReceiptLines : (float) $totalOutstandingBalance;
        $hasTotalOutstanding = $receiptBalance > 0;
    @endphp
    <div class="total-row">
        <span>Total Invoices (this receipt):</span>
        <span><strong>Ksh {{ number_format($totalInvoices, 2) }}</strong></span>
    </div>
    <div class="total-row">
        <span>Payment Made:</span>
        <span><strong>Ksh {{ number_format($amountPaid, 2) }}</strong></span>
    </div>
    <div class="payment-info">
        Payment Method: {{ $payment_method ?? 'Cash' }}@if($reference ?? $payment->reference ?? null), Reference: {{ $reference ?? $payment->reference }}@endif
    </div>
    @php $carriedForward = $payment->unallocated_amount ?? 0; @endphp
    @if($carriedForward > 0)
    <div class="total-row" style="border-top: 1px solid #ddd; padding-top: 5px; margin-top: 5px;">
        <span><strong>Carried Forward:</strong></span>
        <span style="color: {{ $brandSuccess }};"><strong>(Ksh {{ number_format($carriedForward, 2) }})</strong></span>
    </div>
    @endif
    @if($hasTotalOutstanding)
    <div class="total-row grand-total" style="border-top: 1px solid {{ $brandPrimary }}; padding-top: 6px; margin-top: 6px; color: {{ $brandDanger }};">
        <span style="font-size: 12px; font-weight: bold;">Balance (shown above):</span>
        <span style="font-size: 12px; font-weight: bold;">Ksh {{ number_format($receiptBalance, 2) }}</span>
    </div>
    @else
    <div class="total-row grand-total" style="border-top: 1px solid {{ $brandPrimary }}; padding-top: 6px; margin-top: 6px; color: {{ $brandSuccess }};">
        <span style="font-size: 12px; font-weight: bold;">Balance:</span>
        <span style="font-size: 12px; font-weight: bold;">Ksh 0.00</span>
    </div>
    @endif
</div>

<!-- Narration -->
@if($narration ?? $payment->narration)
<div style="margin-top: 8px; padding: 6px; background-color: #f9f9f9; border-left: 2px solid {{ $brandPrimary }}; font-size: 9px;">
    <strong>Narration:</strong> {{ $narration ?? $payment->narration }}
</div>
@endif

<!-- Fee policy notice (prominent, before footer) -->
<div class="receipt-policy-notice">
    <strong>Important:</strong> Fees once paid are not refundable or transferable. No fee rebate will be given for absenteeism.
</div>

<!-- Footer -->
<div class="footer">
    <div class="thank-you">Thank You for Your Payment!</div>
    <div class="footer-meta">This is a computer-generated receipt. No signature required.</div>
    <div class="footer-details">
        <span>Generated: {{ date('d M Y, H:i') }}</span>
        @if(!empty($school['phone'] ?? ''))
            <span class="footer-sep">|</span>
            <span>Contact: {{ $school['phone'] }}</span>
        @endif
    </div>
    @if(!empty($receipt_footer ?? ''))
        <div class="footer-custom">{!! $receipt_footer !!}</div>
    @endif
</div>
