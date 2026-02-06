{{-- Shared receipt body: same content for view, print, and PDF. --}}
@include('layouts.partials.branding-vars')
@php
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
        <span style="font-size: 12px; font-weight: bold;">Balance:</span>
        <span style="font-size: 12px; font-weight: bold;">Ksh {{ number_format($totalOutstandingBalance, 2) }}</span>
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

<!-- Footer -->
<div class="footer">
    <div class="thank-you">Thank You for Your Payment!</div>
    <div>This is a computer-generated receipt. No signature required.</div>
    @php
        $family = $student->family ?? null;
        $updateLink = $family->updateLink ?? null;
    @endphp
    @if($updateLink && $updateLink->is_active)
        @php $profileUpdateUrl = url('/family-update/' . $updateLink->token); @endphp
        <div class="profile-update-link">
            <strong>Update Your Profile:</strong>
            <div style="margin-top: 4px;">
                <a href="{{ $profileUpdateUrl }}" target="_blank" style="color: {{ $brandSecondary }}; text-decoration: none; word-break: break-all; display: inline-block;">
                    {{ $profileUpdateUrl }}
                </a>
            </div>
            <div style="margin-top: 4px; font-size: 9px; color: #666;">
                Click the link above to update student and family information
            </div>
        </div>
    @endif
    <div style="margin-top: 10px;">
        Generated on: {{ date('d M Y, H:i:s') }}<br>
        @if(!empty($school['phone'] ?? ''))For inquiries, contact: {{ $school['phone'] }}@endif
    </div>
    @if(!empty($receipt_footer ?? ''))
        <div style="margin-top: 6px;">{!! $receipt_footer !!}</div>
    @endif
</div>
