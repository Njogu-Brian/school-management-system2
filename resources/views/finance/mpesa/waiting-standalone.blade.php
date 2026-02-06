@php
    $brandPrimary = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $brandMpesaGreen }}">
    <title>Payment - {{ $transaction->student?->full_name ?? 'School fees' }}</title>
    @include('layouts.partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --mpesa-green: {{ $brandMpesaGreen }};
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; min-height: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(160deg, var(--brand-primary) 0%, var(--brand-secondary) 50%, color-mix(in srgb, var(--brand-primary) 80%, var(--brand-secondary)) 100%);
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .waiting-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }
        .spinner { width: 80px; height: 80px; border: 6px solid #f3f3f3; border-top: 6px solid var(--mpesa-green); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .phone-icon { font-size: 64px; color: var(--brand-primary); animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .countdown { font-size: 32px; font-weight: bold; color: var(--mpesa-green); margin: 20px 0; }
        .status-text { font-size: 18px; color: #555; margin: 15px 0; }
        .transaction-details { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: left; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0; }
        .detail-row:last-child { border-bottom: none; }
        .btn-cancel { background: #dc3545; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 20px; }
        .success-icon { font-size: 80px; color: #28a745; animation: scaleIn 0.5s ease-out; }
        .error-icon { font-size: 80px; color: #dc3545; animation: scaleIn 0.5s ease-out; }
        @keyframes scaleIn { 0% { transform: scale(0); } 100% { transform: scale(1); } }
        .btn-action { background: var(--mpesa-green); color: white; border: none; padding: 12px 32px; border-radius: 8px; font-size: 16px; cursor: pointer; margin: 8px; text-decoration: none; display: inline-block; }
        .btn-action:hover { color: white; opacity: 0.95; }
        .receipt-link { margin-top: 16px; font-size: 16px; }
        .receipt-link a { color: var(--mpesa-green); font-weight: 600; }
    </style>
</head>
<body>
    <div class="waiting-card">
        <div id="waitingState">
            <div class="phone-icon"><i class="bi bi-phone-vibrate"></i></div>
            <h3 class="mt-3">Waiting for Payment</h3>
            <p class="status-text">Please check your phone for the M-PESA prompt</p>
            <div class="spinner"></div>
            <div class="countdown" id="countdown">2:00</div>
            <small class="text-muted">Time remaining</small>
            <div class="transaction-details">
                <div class="detail-row"><span><strong>Student:</strong></span><span>{{ $transaction->student?->full_name ?? '—' }}</span></div>
                <div class="detail-row"><span><strong>Amount:</strong></span><span class="text-primary">KES {{ number_format($transaction->amount, 2) }}</span></div>
                <div class="detail-row"><span><strong>Phone:</strong></span><span>{{ $transaction->phone_number }}</span></div>
            </div>
            <p class="small text-muted"><i class="bi bi-info-circle"></i> Enter your M-PESA PIN to complete the payment</p>
            <button type="button" class="btn-cancel" id="btnCancel"><i class="bi bi-x-circle"></i> Cancel</button>
        </div>

        <div id="successState" style="display: none;">
            <div class="success-icon"><i class="bi bi-check-circle-fill"></i></div>
            <h3 class="mt-3 text-success">Payment Successful!</h3>
            <p class="status-text">Your payment has been received and processed.</p>
            <div class="transaction-details">
                <div class="detail-row"><span><strong>Amount Paid:</strong></span><span class="text-success" id="amountPaid">KES {{ number_format($transaction->amount, 2) }}</span></div>
                <div class="detail-row"><span><strong>Receipt No:</strong></span><span id="receiptNumber">-</span></div>
                <div class="detail-row"><span><strong>M-PESA Code:</strong></span><span id="mpesaCode">-</span></div>
            </div>
            <p class="status-text mt-2" id="redirectMessage">Redirecting to your receipt...</p>
            <p class="receipt-link" id="receiptLinkWrap" style="display: none;">
                <a href="#" id="receiptLink">View your receipt</a> if you are not redirected.
            </p>
        </div>

        <div id="failedState" style="display: none;">
            <div class="error-icon"><i class="bi bi-x-circle-fill"></i></div>
            <h3 class="mt-3 text-danger">Payment Failed</h3>
            <p class="status-text" id="errorMessage">The payment could not be completed.</p>
            <div class="transaction-details"><div class="detail-row"><span><strong>Reason:</strong></span><span id="failureReason">-</span></div></div>
            <a href="{{ url()->current() }}" class="btn-action"><i class="bi bi-arrow-clockwise"></i> Try again</a>
        </div>

        <div id="cancelledState" style="display: none;">
            <div class="error-icon"><i class="bi bi-exclamation-triangle-fill text-warning"></i></div>
            <h3 class="mt-3 text-warning">Cancelled</h3>
            <p class="status-text">The payment was cancelled.</p>
            <a href="javascript:window.close();" class="btn-action">Close</a>
        </div>
    </div>

    <script>
    var transactionId = {{ $transaction->id }};
    var statusCheckUrl = @json($statusCheckUrl);
    var cancelUrl = @json($cancelUrl ?? url('/pay/transaction/' . $transaction->id . '/cancel'));
    var receiptBaseUrl = @json(url('/receipt'));
    var pollInterval, countdownInterval, timeRemaining = 120, receiptId = null;

    function checkTransactionStatus() {
        return fetch(statusCheckUrl, { method: 'GET', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { if (!r.ok) throw new Error('Network error'); return r.json(); })
            .then(function(data) {
                if (data.status === 'completed') { showSuccess(data); return Promise.resolve(); }
                if (data.status === 'failed') { showFailed(data); return Promise.resolve(); }
                if (data.status === 'cancelled') { showCancelled(); return Promise.resolve(); }
                return Promise.reject('Still processing');
            });
    }

    function showSuccess(data) {
        if (pollInterval) { clearTimeout(pollInterval); pollInterval = null; }
        if (countdownInterval) clearInterval(countdownInterval);
        document.getElementById('waitingState').style.display = 'none';
        document.getElementById('successState').style.display = 'block';
        if (data.receipt_number) {
            var el = document.getElementById('receiptNumber'); if (el) el.textContent = data.receipt_number;
            receiptId = data.receipt_id;
        }
        if (data.mpesa_code) {
            var el = document.getElementById('mpesaCode'); if (el) el.textContent = data.mpesa_code;
        }
        var token = data.receipt_public_token || null;
        if (token) {
            var receiptUrl = receiptBaseUrl + '/' + token;
            document.getElementById('receiptLink').href = receiptUrl;
            document.getElementById('receiptLinkWrap').style.display = 'block';
            setTimeout(function() { window.location.href = receiptUrl; }, 300);
        } else {
            document.getElementById('redirectMessage').textContent = 'Thank you. Your receipt has been sent to you.';
        }
    }

    function showFailed(data) {
        if (pollInterval) { clearTimeout(pollInterval); pollInterval = null; }
        if (countdownInterval) clearInterval(countdownInterval);
        document.getElementById('waitingState').style.display = 'none';
        document.getElementById('failedState').style.display = 'block';
        if (data.message) { var el = document.getElementById('errorMessage'); if (el) el.textContent = data.message; }
        if (data.failure_reason) { var el = document.getElementById('failureReason'); if (el) el.textContent = data.failure_reason; }
    }

    function showCancelled() {
        if (pollInterval) { clearTimeout(pollInterval); pollInterval = null; }
        if (countdownInterval) clearInterval(countdownInterval);
        document.getElementById('waitingState').style.display = 'none';
        document.getElementById('cancelledState').style.display = 'block';
    }

    function scheduleNext() {
        pollInterval = setTimeout(function() {
            checkTransactionStatus().then(function() { pollInterval = null; }).catch(function() { scheduleNext(); });
        }, 1000);
    }

    document.getElementById('btnCancel').addEventListener('click', function() {
        if (!confirm('Cancel this payment?')) return;
        this.disabled = true;
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(cancelUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ _token: csrf })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) showCancelled(); else { alert(d.message || 'Failed to cancel'); document.getElementById('btnCancel').disabled = false; }
        }).catch(function() { document.getElementById('btnCancel').disabled = false; });
    });

    timeRemaining = 120;
    var cd = document.getElementById('countdown');
    countdownInterval = setInterval(function() {
        if (timeRemaining > 0) { timeRemaining--; if (cd) cd.textContent = Math.floor(timeRemaining/60) + ':' + (timeRemaining%60).toString().padStart(2,'0'); }
        else {
            clearInterval(countdownInterval);
            if (pollInterval) clearTimeout(pollInterval);
            checkTransactionStatus().then().catch(function() {
                showFailed({ message: 'Transaction timeout', failure_reason: 'The payment request has timed out. Please try again.' });
            });
        }
    }, 1000);

    checkTransactionStatus().then(function() {}).catch(function() { scheduleNext(); });
    </script>
</body>
</html>
