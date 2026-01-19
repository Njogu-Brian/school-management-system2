@extends('layouts.app')

@section('content')
<style>
    /* Prevent navigation during payment */
    body.payment-in-progress {
        position: relative;
    }
    body.payment-in-progress::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        display: none;
    }
    .waiting-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .waiting-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    .spinner-container {
        margin: 30px 0;
    }
    .spinner {
        width: 80px;
        height: 80px;
        border: 6px solid #f3f3f3;
        border-top: 6px solid #0f766e;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .phone-icon {
        font-size: 64px;
        color: #0f766e;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    .countdown {
        font-size: 32px;
        font-weight: bold;
        color: #0f766e;
        margin: 20px 0;
    }
    .status-text {
        font-size: 18px;
        color: #555;
        margin: 15px 0;
    }
    .transaction-details {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e0e0e0;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .btn-cancel {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
        transition: background 0.3s;
    }
    .btn-cancel:hover {
        background: #c82333;
    }
    .success-icon {
        font-size: 80px;
        color: #28a745;
        animation: scaleIn 0.5s ease-out;
    }
    .error-icon {
        font-size: 80px;
        color: #dc3545;
        animation: scaleIn 0.5s ease-out;
    }
    @keyframes scaleIn {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .btn-action {
        background: #0f766e;
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
        transition: background 0.3s;
    }
    .btn-action:hover {
        background: #0b5c54;
    }
</style>

<div class="waiting-overlay" id="waitingOverlay">
    <div class="waiting-card">
        <!-- Waiting State -->
        <div id="waitingState">
            <div class="phone-icon">
                <i class="bi bi-phone-vibrate"></i>
            </div>
            <h3 class="mt-3">Waiting for Payment</h3>
            <p class="status-text">Please check your phone for the M-PESA prompt</p>
            
            <div class="spinner-container">
                <div class="spinner"></div>
            </div>
            
            <div class="countdown" id="countdown">2:00</div>
            <small class="text-muted">Time remaining</small>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span><strong>Student:</strong></span>
                    <span>{{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Amount:</strong></span>
                    <span class="text-primary">KES {{ number_format($transaction->amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Phone:</strong></span>
                    <span>{{ $transaction->phone_number }}</span>
                </div>
                @if($transaction->invoice)
                <div class="detail-row">
                    <span><strong>Invoice:</strong></span>
                    <span>{{ $transaction->invoice->invoice_number }}</span>
                </div>
                @endif
            </div>
            
            <p class="small text-muted">
                <i class="bi bi-info-circle"></i>
                Enter your M-PESA PIN to complete the payment
            </p>
            
            <button class="btn-cancel" onclick="cancelTransaction()">
                <i class="bi bi-x-circle"></i> Cancel Transaction
            </button>
        </div>

        <!-- Success State -->
        <div id="successState" style="display: none;">
            <div class="success-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h3 class="mt-3 text-success">Payment Successful!</h3>
            <p class="status-text">Your payment has been received and processed</p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span><strong>Amount Paid:</strong></span>
                    <span class="text-success">KES {{ number_format($transaction->amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Receipt No:</strong></span>
                    <span id="receiptNumber">-</span>
                </div>
                <div class="detail-row">
                    <span><strong>M-PESA Code:</strong></span>
                    <span id="mpesaCode">-</span>
                </div>
            </div>
            
            <button class="btn-action" onclick="window.location.href='{{ route('finance.mpesa.dashboard') }}'">
                <i class="bi bi-house"></i> Back to Dashboard
            </button>
            <button class="btn-action" onclick="viewReceipt()">
                <i class="bi bi-file-text"></i> View Receipt
            </button>
        </div>

        <!-- Failed State -->
        <div id="failedState" style="display: none;">
            <div class="error-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h3 class="mt-3 text-danger">Payment Failed</h3>
            <p class="status-text" id="errorMessage">The payment could not be completed</p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span><strong>Reason:</strong></span>
                    <span id="failureReason">-</span>
                </div>
            </div>
            
            <button class="btn-action" onclick="retry()">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
            <button class="btn-action" onclick="window.location.href='{{ route('finance.mpesa.dashboard') }}'">
                <i class="bi bi-house"></i> Back to Dashboard
            </button>
        </div>

        <!-- Cancelled State -->
        <div id="cancelledState" style="display: none;">
            <div class="error-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h3 class="mt-3 text-warning">Transaction Cancelled</h3>
            <p class="status-text">The payment has been cancelled</p>
            
            <button class="btn-action" onclick="window.location.href='{{ route('finance.mpesa.prompt-payment.form', ['student_id' => $transaction->student_id]) }}'">
                <i class="bi bi-arrow-clockwise"></i> Start New Payment
            </button>
            <button class="btn-action" onclick="window.location.href='{{ route('finance.mpesa.dashboard') }}'">
                <i class="bi bi-house"></i> Back to Dashboard
            </button>
        </div>
    </div>
</div>

<script>
let pollInterval;
let countdownInterval;
let timeRemaining = 120; // 2 minutes
const transactionId = {{ $transaction->id }};
let receiptId = null;

// Prevent navigation
window.addEventListener('beforeunload', function (e) {
    if (document.getElementById('waitingState').style.display !== 'none') {
        e.preventDefault();
        e.returnValue = 'Payment is in progress. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Start polling on page load
$(document).ready(function() {
    startPolling();
    startCountdown();
});

function startPolling() {
    // Poll every 3 seconds
    pollInterval = setInterval(checkTransactionStatus, 3000);
    // Also check immediately
    checkTransactionStatus();
}

function checkTransactionStatus() {
    $.ajax({
        url: '/api/finance/mpesa/transaction/' + transactionId + '/status',
        method: 'GET',
        success: function(response) {
            console.log('Transaction status:', response);
            
            if (response.status === 'completed') {
                showSuccess(response);
            } else if (response.status === 'failed') {
                showFailed(response);
            } else if (response.status === 'cancelled') {
                showCancelled();
            }
            // Continue polling if still processing/pending
        },
        error: function(xhr, status, error) {
            // Continue polling even on error
            console.error('Error checking status:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
        }
    });
}

function startCountdown() {
    // Reset time remaining to 120 seconds
    timeRemaining = 120;
    
    countdownInterval = setInterval(function() {
        if (timeRemaining > 0) {
            timeRemaining--;
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            $('#countdown').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
        } else {
            clearInterval(countdownInterval);
            clearInterval(pollInterval);
            $('#countdown').text('0:00');
            showFailed({ message: 'Transaction timeout', failure_reason: 'The payment request has timed out. Please try again.' });
        }
    }, 1000);
}

function showSuccess(data) {
    clearInterval(pollInterval);
    clearInterval(countdownInterval);
    
    $('#waitingState').hide();
    $('#successState').show();
    
    if (data.receipt_number) {
        $('#receiptNumber').text(data.receipt_number);
        receiptId = data.receipt_id;
    }
    if (data.mpesa_code) {
        $('#mpesaCode').text(data.mpesa_code);
    }
}

function showFailed(data) {
    clearInterval(pollInterval);
    clearInterval(countdownInterval);
    
    $('#waitingState').hide();
    $('#failedState').show();
    
    if (data.message) {
        $('#errorMessage').text(data.message);
    }
    if (data.failure_reason) {
        $('#failureReason').text(data.failure_reason);
    }
}

function showCancelled() {
    clearInterval(pollInterval);
    clearInterval(countdownInterval);
    
    $('#waitingState').hide();
    $('#cancelledState').show();
}

function cancelTransaction() {
    if (!confirm('Are you sure you want to cancel this payment?')) {
        return;
    }
    
    // Disable button to prevent double-click
    $('.btn-cancel').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Cancelling...');
    
    $.ajax({
        url: '/api/finance/mpesa/transaction/' + transactionId + '/cancel',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
        },
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            console.log('Cancel response:', response);
            if (response.success) {
                showCancelled();
            } else {
                alert(response.message || 'Failed to cancel transaction');
                $('.btn-cancel').prop('disabled', false).html('<i class="bi bi-x-circle"></i> Cancel Transaction');
            }
        },
        error: function(xhr, status, error) {
            console.error('Cancel error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            let errorMessage = 'Failed to cancel transaction. Please contact support.';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                }
            } catch(e) {
                // Use default message
            }
            
            alert(errorMessage);
            $('.btn-cancel').prop('disabled', false).html('<i class="bi bi-x-circle"></i> Cancel Transaction');
        }
    });
}

function retry() {
    window.location.href = '{{ route('finance.mpesa.prompt-payment.form', ['student_id' => $transaction->student_id, 'invoice_id' => $transaction->invoice_id]) }}';
}

function viewReceipt() {
    if (receiptId) {
        window.open('/finance/receipts/' + receiptId + '/print', '_blank');
    } else {
        window.location.href = '{{ route('finance.mpesa.transaction.show', $transaction->id) }}';
    }
}
</script>
@endsection

