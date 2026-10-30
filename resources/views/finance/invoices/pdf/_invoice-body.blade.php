{{-- Receipt-style invoice breakdown (single + bulk). --}}
@php
    $brandPrimary = $brandPrimary ?? setting('finance_primary_color', '#3a1a59');
    $brandSuccess = $brandSuccess ?? setting('finance_success_color', '#28a745');
    $brandDanger = $brandDanger ?? setting('finance_danger_color', '#dc3545');
    $school = $school ?? ($branding ?? []);
    $logo = $branding['logoBase64'] ?? ($school['logoBase64'] ?? null);
    if (!$logo && !empty($school['logo'])) {
        $logoPath = public_path('images/' . $school['logo']);
        if (file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    $s = $invoice->student;
    $paymentRows = $paymentRows ?? [];
    $priorBalanceLines = $priorBalanceLines ?? [];
    $priorBalanceTotal = (float) ($priorBalanceTotal ?? 0);
    $pdfBalanceDue = (float) ($pdfBalanceDue ?? $invoice->balance ?? 0);
    $activeItems = $invoice->items->filter(fn ($item) => ($item->status ?? 'active') === 'active');
    $invoiceIsDue = app(\App\Services\StudentFeeLedgerService::class)->invoiceIsDue($invoice);
    $studentUpcoming = (float) ($student_upcoming_balance ?? 0);
    $studentOverdue = (float) ($student_overdue_balance ?? $pdfBalanceDue);
@endphp

<div class="header">
    @if($logo)
    <div style="text-align: center; margin-bottom: 4px;">
        <img src="{{ $logo }}" alt="School Logo" style="max-height: 50px; max-width: 140px;">
    </div>
    @endif
    <h1 class="header-school-name">{{ $school['name'] ?? ($branding['name'] ?? config('app.name', 'SCHOOL NAME')) }}</h1>
    @if(!empty($school['address'] ?? '') || !empty($school['phone'] ?? '') || !empty($school['email'] ?? ''))
    <div class="school-info">
        @if(!empty($school['address'] ?? '')){{ $school['address'] }}<br>@endif
        @if(!empty($school['phone'] ?? ''))Tel: {{ $school['phone'] }}@endif
        @if(!empty($school['phone'] ?? '') && !empty($school['email'] ?? '')) | @endif
        @if(!empty($school['email'] ?? ''))Email: {{ $school['email'] }}@endif
    </div>
    @endif
    @if(!empty($invoiceHeader ?? ''))
    <div class="school-info" style="margin-top: 6px;">{!! $invoiceHeader !!}</div>
    @endif
</div>

<div class="receipt-title">Fee Invoice</div>

<table class="receipt-details-table">
    <tr>
        <td class="detail-label">Student Name:</td>
        <td class="detail-value">{{ $s->full_name ?? 'N/A' }}</td>
        <td class="detail-label" style="text-align: right; width: 25%;">Invoice Number:</td>
        <td class="detail-value" style="text-align: right; width: 25%;"><strong>{{ $invoice->invoice_number }}</strong></td>
    </tr>
    <tr>
        <td class="detail-label">Admission Number:</td>
        <td class="detail-value">{{ $s->admission_number ?? '—' }}</td>
        <td class="detail-label" style="text-align: right;">Date:</td>
        <td class="detail-value" style="text-align: right;">{{ ($printedAt ?? now())->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="detail-label">Class:</td>
        <td class="detail-value">{{ $invoice->classroom->name ?? $s->classroom->name ?? 'N/A' }}@if($invoice->stream->name ?? $s->stream->name ?? null) / {{ $invoice->stream->name ?? $s->stream->name }}@endif</td>
        <td class="detail-label" style="text-align: right;">Status:</td>
        <td class="detail-value" style="text-align: right;">{{ strtoupper($invoice->status ?? '—') }}</td>
    </tr>
    <tr>
        <td class="detail-label">Term (invoice):</td>
        <td class="detail-value">{{ $invoice->termDisplayLabel() ?: (($invoice->year ?? '') . ' / Term ' . ($invoice->term ?? '')) }}</td>
        <td class="detail-label" style="text-align: right;">Due date:</td>
        <td class="detail-value" style="text-align: right;">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</td>
    </tr>
</table>

<table class="allocations-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice Number</th>
            <th>Term</th>
            <th>Votehead</th>
            <th class="text-right">Amount</th>
            <th class="text-right">Discount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($activeItems as $item)
        @php
            $disc = (float) ($item->discount_amount ?? 0);
            $vhName = $item->custom_votehead_name ?: ($item->votehead->name ?? 'N/A');
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $invoice->invoice_number }}</td>
            <td style="font-size: 8px;">{{ $invoice->termDisplayLabel() ?: '—' }}</td>
            <td>{{ $vhName }}</td>
            <td class="text-right">Ksh {{ number_format((float) $item->amount, 2) }}</td>
            <td class="text-right">@if($disc > 0)Ksh {{ number_format($disc, 2) }}@else-@endif</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($priorBalanceTotal > 0.009)
<table class="allocations-table" style="margin-top: 8px;">
    <thead>
        <tr>
            <th>#</th>
            <th colspan="3">Previous unpaid invoice</th>
            <th class="text-right" colspan="2">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($priorBalanceLines as $pl)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td colspan="3">
                {{ $pl['label'] }}
                @if(!empty($pl['invoice_number']))
                    <span style="color:#666;"> ({{ $pl['invoice_number'] }})</span>
                @endif
            </td>
            <td class="text-right" colspan="2">Ksh {{ number_format($pl['balance'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="total-section">
    <div class="total-row">
        <span>Total Invoices (this invoice):</span>
        <span><strong>Ksh {{ number_format((float) $invoice->total, 2) }}</strong></span>
    </div>
    @if($priorBalanceTotal > 0.009)
    <div class="total-row">
        <span>Previous unpaid invoice(s):</span>
        <span><strong>Ksh {{ number_format($priorBalanceTotal, 2) }}</strong></span>
    </div>
    @endif
    <div class="total-row">
        <span>Payment Made (allocated):</span>
        <span><strong>Ksh {{ number_format((float) $invoice->paid_amount, 2) }}</strong></span>
    </div>
    @php
        // Fee balance = overdue only. Upcoming never counts as fee balance.
        $feeBalance = max(0.0, (float) $studentOverdue);
        $invoiceOwnBalance = max(0.0, (float) ($invoice->balance ?? 0));
    @endphp
    @if($studentUpcoming > 0.009)
    <div class="total-row" style="border-top: 1px solid #ddd; padding-top: 5px; margin-top: 5px;">
        <span>Upcoming (not yet due):</span>
        <span><strong>Ksh {{ number_format($studentUpcoming, 2) }}</strong></span>
    </div>
    @endif
    @if(! $invoiceIsDue && $invoiceOwnBalance > 0.009)
    <div class="total-row" style="border-top: 1px solid #ddd; padding-top: 5px; margin-top: 5px;">
        <span>This invoice (upcoming):</span>
        <span><strong>Ksh {{ number_format($invoiceOwnBalance, 2) }}</strong></span>
    </div>
    @endif
    <div class="total-row grand-total" style="border-top: 1px solid {{ $brandPrimary }}; padding-top: 6px; margin-top: 6px; color: {{ $feeBalance > 0.009 ? $brandDanger : $brandSuccess }};">
        <span style="font-size: 12px; font-weight: bold;">Fee balance (overdue):</span>
        <span style="font-size: 12px; font-weight: bold;">Ksh {{ number_format($feeBalance, 2) }}</span>
    </div>
</div>

@if(count($paymentRows) > 0)
<div style="margin-top: 10px;">
    <strong style="font-size: 10px;">Payments applied to this invoice</strong>
    <table class="allocations-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt</th>
                <th>Method</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentRows as $pr)
            <tr>
                <td>{{ !empty($pr['date']) ? \Carbon\Carbon::parse($pr['date'])->format('d/m/Y') : '—' }}</td>
                <td>{{ $pr['receipt'] ?? '—' }}</td>
                <td>{{ $pr['method'] ?? '—' }}</td>
                <td class="text-right">Ksh {{ number_format((float) ($pr['amount'] ?? 0), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="receipt-policy-notice">
    <strong>Important:</strong> Fees once paid are not refundable or transferable. No fee rebate will be given for absenteeism.
</div>

<div class="footer">
    <div class="thank-you">Thank You!</div>
    <div class="footer-meta">This is a computer-generated invoice. No signature required.</div>
    <div class="footer-details">
        <span>Generated: {{ ($printedAt ?? now())->format('d M Y, H:i') }}</span>
        @if(!empty($printedBy ?? null))
            <span class="footer-sep">|</span>
            <span>By: {{ $printedBy }}</span>
        @endif
        @if(!empty($school['phone'] ?? ''))
            <span class="footer-sep">|</span>
            <span>Contact: {{ $school['phone'] }}</span>
        @endif
    </div>
    @if(!empty($invoiceFooter ?? ''))
        <div class="footer-custom">{!! $invoiceFooter !!}</div>
    @endif
</div>
