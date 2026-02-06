<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#007e33">
    <title>Pay School Fees - M-PESA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            /* Branding from settings */
            --brand-primary: {{ \App\Models\Setting::get('finance_primary_color', '#3a1a59') }};
            --brand-secondary: {{ \App\Models\Setting::get('finance_secondary_color', '#14b8a6') }};
            /* M-PESA green for Pay button and balance only */
            --mpesa-green: #007e33;
            --pay-green: var(--mpesa-green);
            --pay-green-light: #00c851;
            --pay-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 50%, color-mix(in srgb, var(--brand-primary) 80%, var(--brand-secondary)) 100%);
            --card-radius: 1rem;
            --tap-min: 44px;
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
            align-items: center;
            justify-content: center;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
            padding: 12px;
        }
        .pay-card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .pay-header {
            background: var(--pay-bg);
            color: #fff;
            padding: 1.5rem 1.25rem;
            text-align: center;
        }
        .pay-header .bi-phone { font-size: 2.5rem; opacity: 0.95; }
        .pay-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0.5rem 0 0; }
        .pay-header .school { font-size: 0.9rem; opacity: 0.9; margin-top: 0.25rem; }
        .pay-body { padding: 1.25rem 1.25rem 1.5rem; }
        .balance-box {
            background: #f0f9f4;
            border: 1px solid #c8e6d0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }
        .balance-box .label { font-size: 0.8rem; color: #555; font-weight: 600; }
        .balance-box .value { font-size: 1.5rem; font-weight: 700; color: var(--mpesa-green); }
        .child-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .child-row:last-child { border-bottom: none; }
        .child-name { font-weight: 600; color: #333; }
        .child-meta { font-size: 0.8rem; color: #666; }
        .child-balance { font-weight: 700; color: var(--mpesa-green); font-size: 0.95rem; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 0.35rem; }
        .form-control, .input-group-text {
            min-height: var(--tap-min);
            font-size: 1rem;
        }
        .btn-pay {
            width: 100%;
            min-height: var(--tap-min);
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            background: var(--mpesa-green);
            color: #fff;
            margin-top: 1.25rem;
            box-shadow: 0 4px 14px rgba(0,126,51,0.35);
        }
        .btn-pay:hover, .btn-pay:focus { color: #fff; background: #006629; opacity: 0.95; transform: translateY(-1px); }
        .btn-pay:disabled { opacity: 0.7; transform: none; }
        .btn-quick {
            min-height: 36px;
            padding: 0.35rem 0.75rem;
            font-size: 0.9rem;
        }
        #statusMessage { margin-top: 1rem; border-radius: 0.75rem; padding: 1rem; display: none; }
        .share-block { background: #f8f9fa; border-radius: 0.75rem; padding: 1rem; margin-top: 0.75rem; }
        .sibling-amount-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .sibling-amount-row input { flex: 0 0 100px; }
        @media (min-width: 576px) {
            body { padding: 24px; }
            .pay-body { padding: 1.5rem 1.5rem 2rem; }
            .pay-card { max-width: 480px; }
        }
    </style>
</head>
<body>
    <div class="pay-card">
        <div class="pay-header">
            <i class="bi bi-phone"></i>
            <h1>M-PESA Payment</h1>
            <p class="school mb-0">{{ \App\Models\Setting::get('school_name', 'School') }}</p>
        </div>

        <div class="pay-body">
            @if($isFamilyLink ?? false)
                {{-- Family link: show all children with balance, share toggle, amounts --}}
                <div class="mb-3">
                    <h5 class="mb-2"><strong>Your children</strong></h5>
                    <p class="small text-muted mb-0">Current fee balance per child. You can pay for one child or split one payment among several.</p>
                </div>
                @php $familyTotalBalance = 0; @endphp
                @foreach($familyStudents ?? [] as $s)
                    @php $familyTotalBalance += (float)($s['fee_balance'] ?? 0); @endphp
                    <div class="child-row">
                        <div>
                            <span class="child-name">{{ $s['full_name'] }}</span>
                            <span class="child-meta d-block">Adm: {{ $s['admission_number'] }} · {{ $s['classroom_name'] ?? '–' }}</span>
                        </div>
                        <div class="child-balance text-end">KES {{ number_format($s['fee_balance'] ?? 0, 2) }}</div>
                    </div>
                @endforeach
                @if(empty($familyStudents))
                    <p class="text-muted small">No students found for this family.</p>
                @else
                    <div class="balance-box mt-2">
                        <span class="label">Total family balance</span>
                        <div class="value">KES {{ number_format($familyTotalBalance, 2) }}</div>
                    </div>
                @endif

                <form id="paymentForm">
                    <input type="hidden" name="share_with_siblings" id="share_with_siblings" value="0">
                    <div class="form-check form-switch mt-3 mb-2">
                        <input class="form-check-input" type="checkbox" id="shareToggle" style="min-width: 3rem; min-height: 1.5rem;">
                        <label class="form-check-label fw-semibold" for="shareToggle">Split this payment among children</label>
                    </div>
                    <p class="small text-muted">One M-PESA transaction; amounts go to each child you choose.</p>

                    <div id="shareBlock" class="share-block" style="display: none;">
                        <p class="small fw-semibold mb-2">Enter amount per child (one transaction):</p>
                        <div id="siblingAllocationsList"></div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="fw-semibold">Total</span>
                            <span id="siblingTotalDisplay" class="text-primary fw-bold">KES 0.00</span>
                        </div>
                    </div>

                    <div id="singleBlock">
                        <label class="form-label mt-2">Paying for</label>
                        <select class="form-select form-select-lg" id="single_student_id" name="student_id" required>
                            <option value="">-- Select child --</option>
                            @foreach($familyStudents ?? [] as $s)
                                <option value="{{ $s['id'] }}" data-balance="{{ $s['fee_balance'] ?? 0 }}">{{ $s['full_name'] }} (KES {{ number_format($s['fee_balance'] ?? 0, 2) }})</option>
                            @endforeach
                        </select>
                        <label class="form-label mt-3">Amount (KES)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">KES</span>
                            <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="1" placeholder="0.00" required>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-quick me-2" id="payFullBtn">Pay full balance</button>
                        </div>
                    </div>
                    <label class="form-label mt-3">Your M-PESA number</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="0712345678" required>
                    </div>
                    <button type="submit" class="btn btn-pay mt-4" id="payBtn"><i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA</button>
                </form>
            @else
                {{-- Single-student link --}}
                @php
                    $student = $paymentLink->student;
                    $feeBalance = 0;
                    if ($student) {
                        $feeBalance = (float) \App\Models\Invoice::where('student_id', $student->id)
                            ->where(function ($q) {
                                $q->where('balance', '>', 0)->orWhereRaw('(COALESCE(total,0) - COALESCE(paid_amount,0)) > 0');
                            })->get()->sum(fn ($inv) => (float)($inv->balance ?? ($inv->total ?? 0) - ($inv->paid_amount ?? 0)));
                    }
                    $maxAmount = max($feeBalance, (float)$paymentLink->amount);
                @endphp
                <div class="text-center mb-3">
                    <h5 class="mb-1">Paying for</h5>
                    <h4 class="mb-0"><strong>{{ $student ? $student->full_name : '–' }}</strong></h4>
                    <p class="text-muted small mb-0">Admission: {{ $student ? $student->admission_number : '–' }}</p>
                </div>
                <div class="balance-box">
                    <span class="label">Current fee balance</span>
                    <div class="value">KES {{ number_format($feeBalance, 2) }}</div>
                    <small class="text-muted">You can pay the full balance or a partial amount.</small>
                </div>
                <form id="paymentForm">
                    <input type="hidden" name="payment_type" value="single">
                    <label class="form-label">Amount (KES)</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">KES</span>
                        <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="1" max="{{ $maxAmount > 0 ? $maxAmount : 99999999 }}" value="{{ $feeBalance > 0 ? $feeBalance : $paymentLink->amount }}" required>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-quick me-2" id="payFullBtn">Pay full balance</button>
                        @if($maxAmount >= 1000)
                        <button type="button" class="btn btn-outline-secondary btn-quick" id="payHalfBtn">Pay half</button>
                        @endif
                    </div>
                    <label class="form-label mt-3">Your M-PESA number</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="0712345678" required>
                    </div>
                    <button type="submit" class="btn btn-pay mt-4" id="payBtn"><i class="bi bi-lock-fill me-2"></i>PAY WITH M-PESA</button>
                </form>
            @endif

            <div id="statusMessage"></div>
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
        var familyStudents = @json($familyStudents ?? []);
        var payUrl = '{{ route("payment.link.process", $paymentLink->hashed_id) }}';
        var token = '{{ csrf_token() }}';

        function showStatus(type, html) {
            var el = document.getElementById('statusMessage');
            el.className = 'alert alert-' + (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger'));
            el.innerHTML = html;
            el.style.display = 'block';
        }

        if (isFamilyLink && familyStudents.length) {
            var feeBalanceMap = {};
            familyStudents.forEach(function(s) { feeBalanceMap[s.id] = parseFloat(s.fee_balance) || 0; });

            $('#shareToggle').on('change', function() {
                var on = $(this).is(':checked');
                $('#share_with_siblings').val(on ? '1' : '0');
                $('#shareBlock').toggle(on);
                $('#singleBlock').toggle(!on);
                if (on) {
                    var list = '';
                    familyStudents.forEach(function(s, i) {
                        list += '<div class="sibling-amount-row"><label class="flex-grow-1 small mb-0">' + s.full_name + ' <span class="text-muted">(bal. KES ' + (s.fee_balance || 0).toLocaleString('en-KE', {minimumFractionDigits: 2}) + ')</span></label><div class="input-group input-group-sm" style="max-width: 120px;"><span class="input-group-text">KES</span><input type="number" class="form-control sibling-amount" step="0.01" min="0" data-student-id="' + s.id + '" data-balance="' + (s.fee_balance || 0) + '" value="0" placeholder="0"></div></div>';
                    });
                    $('#siblingAllocationsList').html(list);
                    $(document).off('input', '.sibling-amount').on('input', '.sibling-amount', function() {
                        var t = 0;
                        $('.sibling-amount').each(function() { t += parseFloat($(this).val()) || 0; });
                        $('#siblingTotalDisplay').text('KES ' + t.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#payment_amount').val(t > 0 ? t.toFixed(2) : '');
                    });
                }
            });

            $('#single_student_id').on('change', function() {
                var bal = $(this).find('option:selected').data('balance');
                if (bal != null) $('#payment_amount').val(parseFloat(bal).toFixed(2));
            });

            $('#payFullBtn').on('click', function() {
                if ($('#shareToggle').is(':checked')) {
                    $('.sibling-amount').each(function() { $(this).val(parseFloat($(this).data('balance') || 0).toFixed(2)); });
                    $('.sibling-amount').first().trigger('input');
                } else {
                    var opt = $('#single_student_id option:selected');
                    if (opt.length && opt.data('balance') != null) $('#payment_amount').val(parseFloat(opt.data('balance')).toFixed(2));
                }
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
            if (isFamilyLink) {
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
            } else {
                payload.amount = parseFloat($('#payment_amount').val()) || 0;
                if (payload.amount < 1) { showStatus('error', 'Enter a valid amount.'); return; }
            }

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
            $('#statusMessage').hide();

            $.ajax({ url: payUrl, method: 'POST', data: payload })
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
