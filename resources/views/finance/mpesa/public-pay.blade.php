@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
    $mpesaStkMaxKes = \App\Services\PaymentGateways\MpesaGateway::STK_MAX_AMOUNT_KES;
    $schoolName = setting('school_name') ?? config('app.name', 'School');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $brandMpesaGreen }}">
    <title>Pay School Fees - {{ $schoolName }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --mpesa-green: {{ $brandMpesaGreen }};
            --pay-bg: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 50%, color-mix(in srgb, var(--brand-primary) 80%, var(--brand-secondary)) 100%);
            --tap-min: 48px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--pay-bg);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 16px;
        }
        .pay-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            margin: auto;
        }
        .pay-header {
            background: var(--pay-bg);
            color: #fff;
            text-align: center;
            padding: 1.5rem 1.25rem;
        }
        .pay-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0.5rem 0 0; }
        .pay-body { padding: 1.25rem; }
        .form-label { font-weight: 600; font-size: 0.9rem; }
        .form-control { min-height: var(--tap-min); font-size: 1rem; }
        .btn-pay {
            width: 100%;
            min-height: var(--tap-min);
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            background: var(--mpesa-green);
            color: #fff;
            margin-top: 1rem;
        }
        .btn-pay:disabled { opacity: 0.65; }
        .student-pick {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }
        .student-pick:hover, .student-pick.selected {
            border-color: var(--mpesa-green);
            background: #f0fdf4;
        }
        .balance-box {
            background: #f0f9f4;
            border: 1px solid #c8e6d0;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .balance-box .value { font-size: 1.4rem; font-weight: 700; color: var(--mpesa-green); }
        #lookupResults { max-height: 240px; overflow-y: auto; }
        .hint { font-size: 0.85rem; color: #6b7280; }
    </style>
    @include('finance.partials.mobile-public-viewport')
</head>
<body class="pay-public-body">
<div class="pay-card">
    <div class="pay-header">
        <i class="bi bi-phone" style="font-size:2.25rem;"></i>
        <h1>Pay School Fees</h1>
        <div class="opacity-90">{{ $schoolName }}</div>
    </div>
    <div class="pay-body">
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('payment.public.process') }}" id="payForm">
            @csrf
            <input type="hidden" name="student_id" id="studentId" value="{{ old('student_id', $selectedStudent?->id) }}">

            <div class="mb-3">
                <label class="form-label" for="studentQuery">Child's name or admission number</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="studentQuery" name="q"
                           value="{{ old('q', $prefill) }}"
                           placeholder="e.g. Jane Doe or RKS/001"
                           autocomplete="off" required>
                    <button type="button" class="btn btn-outline-secondary" id="btnLookup">Find</button>
                </div>
                <div class="hint mt-1">Search, then tap the correct child below.</div>
            </div>

            <div id="lookupResults" class="mb-3" style="display:none;"></div>

            <div id="selectedStudentBox" class="{{ $selectedStudent ? '' : 'd-none' }}">
                <div class="balance-box">
                    <div class="small text-muted fw-semibold">Selected student</div>
                    <div class="fw-bold" id="selectedName">{{ $selectedStudent?->full_name }}</div>
                    <div class="small text-muted" id="selectedMeta">
                        @if($selectedStudent)
                            {{ $selectedStudent->admission_number }}
                            @if($selectedStudent->classroom) · {{ $selectedStudent->classroom->name }} @endif
                        @endif
                    </div>
                    @if($selectedStudent)
                        <div class="mt-2">
                            <span class="small text-muted">Outstanding fees</span>
                            <div class="value" id="selectedBalance">KES {{ number_format($feeBalance, 2) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="phone_number">M-PESA phone number</label>
                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                       value="{{ old('phone_number', $prefillPhone ?? '') }}" placeholder="07XX XXX XXX" inputmode="tel" required>
            </div>

            <div class="mb-2">
                <label class="form-label" for="amount">Amount to pay (KES)</label>
                <input type="number" class="form-control" id="amount" name="amount" min="1"
                       max="{{ $mpesaStkMaxKes }}" step="1"
                       value="{{ old('amount') }}"
                       placeholder="Enter amount" required>
                @if($feeBalance > 0)
                    <button type="button" class="btn btn-sm btn-outline-success mt-2" id="btnPayFull">
                        Pay full balance ({{ number_format($feeBalance, 0) }})
                    </button>
                @endif
            </div>

            <p class="hint mb-0">
                <i class="bi bi-shield-check"></i>
                You will receive an M-PESA prompt on this phone. Enter your PIN to complete payment.
            </p>

            <button type="submit" class="btn btn-pay" id="btnSubmit" {{ $selectedStudent ? '' : 'disabled' }}>
                <i class="bi bi-send"></i> Send M-PESA prompt
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const lookupUrl = @json(route('payment.public.lookup'));
    const queryInput = document.getElementById('studentQuery');
    const resultsEl = document.getElementById('lookupResults');
    const studentIdInput = document.getElementById('studentId');
    const selectedBox = document.getElementById('selectedStudentBox');
    const btnSubmit = document.getElementById('btnSubmit');
    const amountInput = document.getElementById('amount');
    const btnPayFull = document.getElementById('btnPayFull');

    function renderResults(students) {
        if (!students.length) {
            resultsEl.innerHTML = '<div class="alert alert-warning py-2 mb-0">No matching student found. Check the name or admission number.</div>';
            resultsEl.style.display = 'block';
            return;
        }
        resultsEl.innerHTML = students.map(s => `
            <div class="student-pick" data-id="${s.id}" data-name="${s.full_name}" data-adm="${s.admission_number || ''}"
                 data-class="${s.classroom_name || ''}" data-balance="${s.fee_balance}" data-phone="${s.parent_phone || ''}">
                <div class="fw-semibold">${s.full_name}</div>
                <div class="small text-muted">${s.admission_number || ''}${s.classroom_name ? ' · ' + s.classroom_name : ''}</div>
                <div class="small fw-bold text-success">Balance: KES ${Number(s.fee_balance).toLocaleString()}</div>
            </div>
        `).join('');
        resultsEl.style.display = 'block';
        resultsEl.querySelectorAll('.student-pick').forEach(el => {
            el.addEventListener('click', () => selectStudent(el));
        });
        if (students.length === 1) {
            selectStudent(resultsEl.querySelector('.student-pick'));
        }
    }

    function selectStudent(el) {
        resultsEl.querySelectorAll('.student-pick').forEach(n => n.classList.remove('selected'));
        el.classList.add('selected');
        const id = el.dataset.id;
        const balance = parseFloat(el.dataset.balance || '0');
        studentIdInput.value = id;
        document.getElementById('selectedName').textContent = el.dataset.name;
        document.getElementById('selectedMeta').textContent = [el.dataset.adm, el.dataset.class].filter(Boolean).join(' · ');
        const balEl = document.getElementById('selectedBalance');
        if (balEl) balEl.textContent = 'KES ' + balance.toLocaleString(undefined, {minimumFractionDigits: 2});
        selectedBox.classList.remove('d-none');
        btnSubmit.disabled = false;
        const phoneInput = document.getElementById('phone_number');
        if (phoneInput && el.dataset.phone && !phoneInput.value) {
            phoneInput.value = el.dataset.phone;
        }
    }

    async function doLookup() {
        const q = queryInput.value.trim();
        if (q.length < 2) return;
        resultsEl.innerHTML = '<div class="text-muted small py-2">Searching…</div>';
        resultsEl.style.display = 'block';
        try {
            const res = await fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ q }),
            });
            const data = await res.json();
            renderResults(data.students || []);
        } catch (e) {
            resultsEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">Search failed. Please try again.</div>';
        }
    }

    document.getElementById('btnLookup').addEventListener('click', doLookup);
    queryInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doLookup(); } });

    if (btnPayFull) {
        btnPayFull.addEventListener('click', () => {
            const bal = parseFloat(@json($feeBalance));
            if (bal > 0) amountInput.value = Math.ceil(bal);
        });
    }

    @if($selectedStudent)
        btnSubmit.disabled = false;
    @elseif($prefill)
        doLookup();
    @endif
})();
</script>
</body>
</html>
