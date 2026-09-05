@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
    $mpesaStkMaxKes = \App\Services\PaymentGateways\MpesaGateway::STK_MAX_AMOUNT_KES;
    $familyTotalRefBalance = 0.0;
    if (($isFamilyLink ?? false) && ($showFamilySplitUi ?? false) && !empty($familyStudents ?? [])) {
        foreach ($familyStudents as $s) {
            $familyTotalRefBalance += (float) ($s['fee_balance'] ?? 0);
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $brandMpesaGreen }}">
    <title>Pay School Fees - M-PESA</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            /* Branding from settings */
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            /* Pay button color (M-PESA green or custom) */
            --mpesa-green: {{ $brandMpesaGreen }};
            --pay-green: var(--mpesa-green);
            --pay-green-light: #00c851;
            --pay-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 50%, color-mix(in srgb, var(--brand-primary) 80%, var(--brand-secondary)) 100%);
            --card-radius: 1rem;
            --tap-min: 48px;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--pay-bg);
            color: #1a1a1a;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: max(12px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left));
        }
        .pay-card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
        }
        .pay-header {
            background: var(--pay-bg);
            color: #fff;
            padding: 1.25rem 1.1rem;
            text-align: center;
        }
        .pay-header .bi-phone { font-size: 2.15rem; opacity: 0.95; }
        .pay-header h1 { font-size: 1.25rem; font-weight: 700; margin: 0.45rem 0 0; }
        .pay-header .school { font-size: 0.88rem; opacity: 0.9; margin-top: 0.25rem; }
        .pay-body { padding: 1.1rem 1.05rem 1.35rem; }
        .balance-box {
            background: #f0f9f4;
            border: 1px solid #c8e6d0;
            border-radius: 0.75rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1.1rem;
        }
        .balance-box .label { font-size: 0.8rem; color: #555; font-weight: 600; }
        .balance-box .value { font-size: 1.4rem; font-weight: 700; color: var(--mpesa-green); }
        .child-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0;
            border-bottom: 1px solid #eee;
        }
        .child-row:last-child { border-bottom: none; }
        .child-name { font-weight: 700; color: #333; font-size: .98rem; }
        .child-meta { font-size: 0.8rem; color: #666; margin-top: .1rem; }
        .child-balance { font-weight: 700; color: var(--mpesa-green); font-size: 0.95rem; white-space: nowrap; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 0.35rem; }
        .form-control, .input-group-text {
            min-height: var(--tap-min);
            font-size: 1rem;
        }
        .btn-pay {
            width: 100%;
            min-height: 48px;
            font-size: 1.05rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            background: var(--mpesa-green);
            color: #fff;
            margin-top: 1.1rem;
            box-shadow: 0 4px 14px rgba(0,126,51,0.35);
        }
        .btn-pay:hover, .btn-pay:focus { color: #fff; background: #006629; opacity: 0.95; }
        .btn-pay:disabled { opacity: 0.7; }
        .btn-quick {
            min-height: 44px;
            padding: 0.45rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
        #statusMessage { margin-top: 1rem; border-radius: 0.75rem; padding: 1rem; display: none; }
        .share-block { background: #f8f9fa; border-radius: 0.75rem; padding: 1rem; margin-top: 0.75rem; }
        .sibling-amount-row {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.55rem;
        }
        .sibling-amount-row input {
            flex: 1 1 auto;
            min-width: 0;
            max-width: 140px;
            min-height: 44px;
        }
        .invoice-breakdown {
            margin: 0.35rem 0 0.75rem;
            padding: 0.55rem 0.65rem;
            background: #fafafa;
            border-radius: 0.5rem;
            border: 1px solid #eee;
        }
        .invoice-breakdown .inv-line {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.35rem 0.75rem;
            padding: 0.45rem 0;
            border-bottom: 1px solid #ececec;
            font-size: 0.84rem;
        }
        .invoice-breakdown .inv-line:last-child { border-bottom: none; }
        .invoice-breakdown .inv-meta { color: #555; flex: 1 1 auto; min-width: 0; word-break: break-word; }
        .invoice-breakdown .inv-bal { font-weight: 700; color: #c62828; white-space: nowrap; }
        .invoice-block { padding-bottom: 0.35rem; margin-bottom: 0.35rem; border-bottom: 1px solid #ececec; }
        .invoice-block:last-child { border-bottom: none; margin-bottom: 0; }
        .inv-line-header { font-weight: 600; }
        .inv-line-item { font-size: 0.8rem; padding-left: 0.45rem; color: #444; }
        .inv-line-item .inv-meta { font-weight: 400; }
        .inv-item-amt { font-weight: 600; color: #333; white-space: nowrap; }
        .breakdown-heading { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #666; margin: 0.25rem 0 0.15rem; }
        @media (min-width: 576px) {
            body { padding: 24px; align-items: center; }
            .pay-body { padding: 1.4rem 1.4rem 1.75rem; }
            .pay-card { max-width: 680px; }
            .pay-header { padding: 1.5rem 1.25rem; }
        }
        @media (min-width: 992px) {
            .pay-card { max-width: 720px; }
        }
    </style>
    @include('finance.partials.mobile-public-viewport')
</head>
<body class="pay-public-body">
    <div class="pay-card">
        <div class="pay-header">
            <i class="bi bi-phone"></i>
            <h1>M-PESA Payment</h1>
            <p class="school mb-0">{{ \App\Models\Setting::get('school_name', 'School') }}</p>
        </div>

        <div class="pay-body">
            @if(($isFamilyLink ?? false) && ($showFamilySplitUi ?? false))
                {{-- Family link with 2+ children: split payment UI --}}
                <div class="mb-3">
                    <h5 class="mb-2"><strong>Your children</strong></h5>
                    <p class="small text-muted mb-0">Fee balance per child. Split one M-PESA payment across children, or pay one child only.</p>
                </div>
                @php $familyTotalBalance = 0; @endphp
                @foreach($familyStudents ?? [] as $s)
                    @php $familyTotalBalance += (float)($s['fee_balance'] ?? 0); @endphp
                    <div class="mb-2">
                        <div class="child-row">
                            <div>
                                <span class="child-name">{{ $s['full_name'] }}</span>
                                <span class="child-meta d-block">Adm: {{ $s['admission_number'] }} · {{ $s['classroom_name'] ?? '–' }}</span>
                            </div>
                            <div class="child-balance text-end">KES {{ number_format($s['fee_balance'] ?? 0, 2) }}</div>
                        </div>
                        @if(!empty($s['invoices']))
                            <p class="breakdown-heading mb-0">Unpaid invoices &amp; items</p>
                            <div class="invoice-breakdown">
                                @include('finance.mpesa.partials.unpaid-invoice-breakdown', ['invoices' => $s['invoices']])
                            </div>
                        @endif
                    </div>
                @endforeach
                @if(empty($familyStudents))
                    <p class="text-muted small">No students found for this family.</p>
                @else
                    <div class="balance-box mt-2">
                        <span class="label">Total family balance</span>
                        <div class="value">KES {{ number_format($familyTotalBalance, 2) }}</div>
                    </div>
                    <p class="small text-muted mb-0">You may pay more than the balance; extra is credited. M-PESA limit KES {{ number_format($mpesaStkMaxKes) }} per transaction.</p>
                @endif

                <form id="paymentForm" novalidate>
                    <input type="hidden" name="share_with_siblings" id="share_with_siblings" value="1">
                    <div class="form-check form-switch mt-3 mb-2">
                        <input class="form-check-input" type="checkbox" id="shareToggle" style="min-width: 3rem; min-height: 1.5rem;" checked>
                        <label class="form-check-label fw-semibold" for="shareToggle">Pay for all children in one transaction</label>
                    </div>
                    <p class="small text-muted">Enter the amount you want to pay. We split it across children (full invoices first, otherwise equal shares rounded to KES 10). You can still edit each child.</p>

                    <div id="shareBlock" class="share-block">
                        <label class="form-label" for="family_payment_amount">Amount to pay (KES)</label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text">KES</span>
                            <input type="number" class="form-control" id="family_payment_amount" inputmode="numeric" step="1" min="1" placeholder="Enter amount" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary btn-quick me-2" id="payFullFamilyBtn">Pay full family balance</button>
                        </div>
                        <p class="small fw-semibold mb-2">Amount per child (editable):</p>
                        <div id="siblingAllocationsList"></div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="fw-semibold">Total</span>
                            <span id="siblingTotalDisplay" class="text-primary fw-bold">KES 0.00</span>
                        </div>
                    </div>

                    <div id="singleBlock" style="display: none;">
                        <label class="form-label mt-2">Paying for one child only</label>
                        <select class="form-select form-select-lg" id="single_student_id" name="student_id">
                            <option value="">-- Select child --</option>
                            @foreach($familyStudents ?? [] as $s)
                                <option value="{{ $s['id'] }}" data-balance="{{ $s['fee_balance'] ?? 0 }}">{{ $s['full_name'] }} (KES {{ number_format($s['fee_balance'] ?? 0, 2) }})</option>
                            @endforeach
                        </select>
                        <label class="form-label mt-3">Amount (KES)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">KES</span>
                            <input type="number" class="form-control" id="payment_amount" name="amount" step="1" min="1" placeholder="0">
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-quick me-2" id="payFullBtn">Pay full balance</button>
                        </div>
                    </div>
                    <label class="form-label mt-3">Your M-PESA number</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="0712345678" value="{{ $prefillPhone ?? '' }}" required>
                    </div>
                    <button type="submit" class="btn btn-pay mt-4" id="payBtn"><i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA</button>
                </form>
            @elseif($displayStudentForPay ?? null)
                {{-- Per-student payment link OR family link with only one child (simple UI, no split) --}}
                @php
                    $student = $displayStudentForPay;
                    $feeBalance = (float) ($feeBalance ?? 0);
                @endphp
                <div class="text-center mb-3">
                    <h5 class="mb-1">Paying for</h5>
                    <h4 class="mb-0"><strong>{{ $student->full_name ?? '–' }}</strong></h4>
                    <p class="text-muted small mb-0">Admission: {{ $student->admission_number ?? '–' }} @if($isFamilyLink ?? false)<span> · Family account</span>@endif</p>
                </div>
                @if(!empty($singleStudentInvoices))
                    <p class="breakdown-heading mb-1">Unpaid invoices &amp; items</p>
                    <div class="invoice-breakdown mb-3">
                        @include('finance.mpesa.partials.unpaid-invoice-breakdown', ['invoices' => $singleStudentInvoices])
                    </div>
                @endif
                <div class="balance-box">
                    <span class="label">Current fee balance</span>
                    <div class="value">KES {{ number_format($feeBalance, 2) }}</div>
                    <small class="text-muted">Pay in full, in part, or more (overpayment is credited). M-PESA limit KES {{ number_format($mpesaStkMaxKes) }} per transaction.</small>
                </div>
                <form id="paymentForm" novalidate>
                    <input type="hidden" name="payment_type" value="single">
                    @if($isFamilyLink ?? false)
                        <input type="hidden" id="family_single_student_id" name="family_single_student_id" value="{{ $student->id }}">
                    @endif
                    <label class="form-label">Amount (KES)</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">KES</span>
                        <input type="number" class="form-control" id="payment_amount" name="amount" step="1" min="1" max="{{ $mpesaStkMaxKes }}" value="" placeholder="Enter amount" inputmode="numeric" autocomplete="off" required>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-quick me-2" id="payFullBtn">Pay full balance</button>
                        @if($feeBalance >= 1000)
                        <button type="button" class="btn btn-outline-secondary btn-quick" id="payHalfBtn">Pay half</button>
                        @endif
                    </div>
                    <label class="form-label mt-3">Your M-PESA number</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="0712345678" value="{{ $prefillPhone ?? '' }}" required>
                    </div>
                    <button type="submit" class="btn btn-pay mt-4" id="payBtn"><i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA</button>
                </form>
            @else
                <p class="text-muted text-center small">Unable to load this payment page. The link may be invalid.</p>
            @endif

            <div id="statusMessage"></div>

            @if(!empty($reportPortalUrl))
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ $reportPortalUrl }}" class="btn btn-outline-primary w-100 btn-portal" style="min-height:44px;border-radius:.75rem;font-weight:600;">
                        <i class="bi bi-journal-text me-1"></i> View Term Report Cards
                    </a>
                </div>
            @endif

            <p class="text-center small text-muted mt-3 mb-0">
                <i class="bi bi-shield-check"></i> Secure M-PESA · Safaricom
            </p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        var isFamilyLink = {{ ($isFamilyLink ?? false) ? 'true' : 'false' }};
        var showFamilySplitUi = {{ ($showFamilySplitUi ?? false) ? 'true' : 'false' }};
        var familyStudents = @json($familyStudents ?? []);
        var payUrl = '{{ route("payment.link.process", $paymentLink->hashed_id) }}';
        var token = '{{ csrf_token() }}';
        var mpesaStkMax = {{ (int) $mpesaStkMaxKes }};
        var familyTotalRefBalance = {{ number_format($familyTotalRefBalance, 2, '.', '') }};
        var singleRefBalance = {{ number_format((float) ($feeBalance ?? 0), 2, '.', '') }};

        function showStatus(type, html) {
            var el = document.getElementById('statusMessage');
            el.className = 'alert alert-' + (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger'));
            el.innerHTML = html;
            el.style.display = 'block';
        }

        function getReferenceBalanceForOverpayment() {
            if (isFamilyLink && showFamilySplitUi) {
                if ($('#shareToggle').is(':checked')) {
                    return familyTotalRefBalance;
                }
                var opt = $('#single_student_id option:selected');
                var b = opt.data('balance');
                return b != null ? parseFloat(b) : 0;
            }
            return singleRefBalance;
        }

        function confirmOverpaymentIfNeeded(amt) {
            var ref = getReferenceBalanceForOverpayment();
            if (amt <= ref + 0.009) {
                return true;
            }
            var over = amt - ref;
            return window.confirm('You are paying KES ' + amt.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                ', which is more than the outstanding fee balance of KES ' + ref.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                ' (overpayment KES ' + over.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '). The extra amount will be credited to the account. Continue?');
        }

        function assertMpesaStkMax(amt) {
            if (amt > mpesaStkMax + 0.01) {
                showStatus('error', 'M-PESA allows up to KES ' + mpesaStkMax.toLocaleString('en-KE') + ' per transaction. Enter a lower amount or split into multiple payments.');
                return false;
            }
            return true;
        }

        function roundToTen(n) {
            return Math.round(n / 10) * 10;
        }

        function smartAllocate(balances, payment) {
            var n = balances.length;
            var alloc = balances.map(function () { return 0; });
            if (n === 0 || payment <= 0) return alloc;
            var total = balances.reduce(function (s, b) { return s + b; }, 0);
            if (payment >= total - 0.009) {
                alloc = balances.slice();
                if (payment > total) alloc[0] += payment - total;
                return alloc;
            }
            var maxBal = Math.max.apply(null, balances);
            if (payment >= total - maxBal - 0.009) {
                var shortfall = total - payment;
                var absorbIdx = balances.indexOf(maxBal);
                alloc = balances.map(function (b, i) {
                    return i === absorbIdx ? Math.max(0, b - shortfall) : b;
                });
                return alloc;
            }
            var remaining = payment;
            var open = [];
            balances.forEach(function (b, i) { if (b > 0) open.push(i); });
            while (remaining >= 5 && open.length) {
                var share = roundToTen(remaining / open.length);
                if (share < 10 && remaining >= 10) share = 10;
                if (share <= 0) break;
                var nextOpen = [];
                var progressed = false;
                open.forEach(function (i) {
                    var room = balances[i] - alloc[i];
                    var give = Math.min(share, room, remaining);
                    give = roundToTen(give);
                    if (give > remaining) give = remaining;
                    if (give > 0) {
                        alloc[i] += give;
                        remaining -= give;
                        progressed = true;
                    }
                    if (balances[i] - alloc[i] >= 5 && remaining > 0) nextOpen.push(i);
                });
                if (!progressed) break;
                open = nextOpen;
            }
            if (remaining > 0) {
                balances.forEach(function (b, i) {
                    if (remaining <= 0) return;
                    var room = b - alloc[i];
                    if (room <= 0) return;
                    var give = Math.min(room, remaining);
                    alloc[i] += give;
                    remaining -= give;
                });
            }
            return alloc;
        }

        if (isFamilyLink && showFamilySplitUi && familyStudents.length > 1) {
            var applyingSplit = false;

            function refreshSiblingTotal() {
                var t = 0;
                $('.sibling-amount').each(function() { t += parseFloat($(this).val()) || 0; });
                $('#siblingTotalDisplay').text('KES ' + t.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                if (!applyingSplit) {
                    $('#family_payment_amount').val(t > 0 ? String(Math.round(t)) : '');
                }
            }

            function applySmartSplit(amount) {
                var bals = familyStudents.map(function (s) { return parseFloat(s.fee_balance) || 0; });
                var split = smartAllocate(bals, amount);
                applyingSplit = true;
                $('.sibling-amount').each(function (idx) {
                    $(this).val(split[idx] > 0 ? String(split[idx]) : '');
                });
                applyingSplit = false;
                refreshSiblingTotal();
            }

            function buildSiblingList() {
                var list = '';
                familyStudents.forEach(function(s) {
                    var bal = parseFloat(s.fee_balance) || 0;
                    list += '<div class="sibling-amount-row"><label class="flex-grow-1 small mb-0">' + s.full_name + ' <span class="text-muted">(bal. KES ' + (bal).toLocaleString('en-KE', {minimumFractionDigits: 2}) + ')</span></label><div class="input-group input-group-sm" style="max-width: 140px;"><span class="input-group-text">KES</span><input type="number" class="form-control sibling-amount" step="1" min="0" data-student-id="' + s.id + '" data-balance="' + bal + '" value="" placeholder="0"></div></div>';
                });
                $('#siblingAllocationsList').html(list);
                $(document).off('input', '.sibling-amount').on('input', '.sibling-amount', refreshSiblingTotal);
                refreshSiblingTotal();
            }

            function syncSingleStudentRequired() {
                var singleVisible = $('#singleBlock').is(':visible');
                var sel = $('#single_student_id')[0];
                if (sel) sel.required = singleVisible;
                var amt = $('#payment_amount')[0];
                if (amt) amt.required = singleVisible;
            }

            $('#shareToggle').on('change', function() {
                var on = $(this).is(':checked');
                $('#share_with_siblings').val(on ? '1' : '0');
                $('#shareBlock').toggle(on);
                $('#singleBlock').toggle(!on);
                syncSingleStudentRequired();
                if (on) buildSiblingList();
            });

            buildSiblingList();
            syncSingleStudentRequired();

            $('#family_payment_amount').on('input', function () {
                var amt = parseFloat($(this).val()) || 0;
                if (amt >= 1) applySmartSplit(amt);
            });

            $('#payFullFamilyBtn').on('click', function () {
                var total = familyStudents.reduce(function (s, st) { return s + (parseFloat(st.fee_balance) || 0); }, 0);
                $('#family_payment_amount').val(total > 0 ? String(Math.round(total)) : '');
                applySmartSplit(total);
            });

            $('#single_student_id').on('change', function() {
                var bal = $(this).find('option:selected').data('balance');
                if (bal != null) $('#payment_amount').val(parseFloat(bal).toFixed(0));
            });

            $('#payFullBtn').on('click', function() {
                var opt = $('#single_student_id option:selected');
                if (opt.length && opt.data('balance') != null) $('#payment_amount').val(parseFloat(opt.data('balance')).toFixed(0));
            });
        } else {
            var fullBalance = {{ ($feeBalance ?? 0) }};
            var linkAmount = {{ (float)($paymentLink->amount ?? 0) }};
            var maxAmt = Math.max(fullBalance, linkAmount) || linkAmount || 1;
            $('#payFullBtn').on('click', function() { $('#payment_amount').val((fullBalance > 0 ? fullBalance : maxAmt).toFixed(2)); });
            var halfBtn = document.getElementById('payHalfBtn');
            if (halfBtn) halfBtn.onclick = function() { $('#payment_amount').val((maxAmt / 2).toFixed(2)); };
        }

        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('phone')) $('#phone_number').val(urlParams.get('phone'));

        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            var phone = $('#phone_number').val().trim();
            var btn = $('#payBtn');
            if (!phone) { showStatus('error', 'Please enter your phone number.'); return; }

            var payload = { phone_number: phone, _token: token };
            if (isFamilyLink && showFamilySplitUi) {
                if ($('#shareToggle').is(':checked')) {
                    var allocs = [];
                    $('.sibling-amount').each(function() {
                        var am = parseFloat($(this).val()) || 0;
                        if (am > 0) allocs.push({ student_id: $(this).data('student-id'), amount: am });
                    });
                    if (!allocs.length) { showStatus('error', 'Enter at least one amount when splitting.'); return; }
                    payload.share_with_siblings = 1;
                    payload.sibling_allocations = allocs;
                    payload.amount = allocs.reduce(function(s, a) { return s + a.amount; }, 0);
                } else {
                    var sid = $('#single_student_id').val();
                    var am = parseFloat($('#payment_amount').val()) || 0;
                    if (!sid || am < 1) { showStatus('error', 'Select a child and enter an amount.'); return; }
                    payload.student_id = sid;
                    payload.amount = am;
                }
            } else if (isFamilyLink && !showFamilySplitUi) {
                var fsid = document.getElementById('family_single_student_id');
                var sid = fsid ? parseInt(fsid.value, 10) : null;
                var am = parseFloat($('#payment_amount').val()) || 0;
                if (!sid || am < 1) { showStatus('error', 'Enter a valid amount.'); return; }
                payload.student_id = sid;
                payload.amount = am;
            } else {
                payload.amount = parseFloat($('#payment_amount').val()) || 0;
                if (payload.amount < 1) { showStatus('error', 'Enter a valid amount.'); return; }
            }

            if (!assertMpesaStkMax(payload.amount)) { return; }
            if (!confirmOverpaymentIfNeeded(payload.amount)) { return; }

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
            $('#statusMessage').hide();

            $.ajax({
                url: payUrl,
                method: 'POST',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: $.param(payload),
                traditional: true
            })
                .done(function(res) {
                    if (res.success) {
                        if (res.transaction_id) {
                            window.location.href = '{{ url("/pay/waiting") }}/' + res.transaction_id;
                        } else {
                            showStatus('success', '<strong>Request sent.</strong> Enter your M-PESA PIN on your phone to complete the payment.');
                            setTimeout(function() { location.reload(); }, 5000);
                        }
                    } else {
                        btn.prop('disabled', false).html('<i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA');
                        showStatus('error', res.message || 'Request failed.');
                    }
                })
                .fail(function(xhr) {
                    btn.prop('disabled', false).html('<i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Try again.';
                    showStatus('error', msg);
                });
        });
    })();
    </script>
</body>
</html>
