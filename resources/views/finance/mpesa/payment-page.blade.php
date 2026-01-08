<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pay School Fees - M-PESA</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .payment-header {
            background: linear-gradient(135deg, #00c851 0%, #007e33 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .payment-body {
            padding: 30px;
        }
        .amount-display {
            font-size: 36px;
            font-weight: bold;
            color: #00c851;
            text-align: center;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            color: #333;
            font-weight: 600;
        }
        .btn-pay {
            background: linear-gradient(135deg, #00c851 0%, #007e33 100%);
            border: none;
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,200,81,0.3);
        }
        .btn-pay:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        #statusMessage {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="payment-header">
            <i class="fas fa-mobile-alt"></i>
            <h2>M-PESA Payment</h2>
            <p class="mb-0">{{ \App\Models\Setting::getValue('school_name', 'School') }}</p>
        </div>
        
        <div class="payment-body">
            <div class="text-center mb-4">
                <h5>Payment For:</h5>
                <h4><strong>{{ $paymentLink->student->first_name }} {{ $paymentLink->student->last_name }}</strong></h4>
                <p class="text-muted mb-0">Admission No: {{ $paymentLink->student->admission_number }}</p>
                @if($paymentLink->invoice)
                    <p class="text-muted mb-0">Invoice: {{ $paymentLink->invoice->invoice_number }}</p>
                @endif
            </div>

            <div class="amount-display">
                KES {{ number_format($paymentLink->amount, 2) }}
            </div>

            <div class="mt-4">
                <div class="info-row">
                    <span class="info-label">Description:</span>
                    <span class="info-value">{{ $paymentLink->description }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Currency:</span>
                    <span class="info-value">{{ $paymentLink->currency }}</span>
                </div>
                @if($paymentLink->expires_at)
                <div class="info-row">
                    <span class="info-label">Link Expires:</span>
                    <span class="info-value">{{ $paymentLink->expires_at->format('d M Y, H:i') }}</span>
                </div>
                @endif
            </div>

            <form id="paymentForm">
                <div class="form-group mt-4">
                    <label for="payment_amount"><strong>Payment Amount</strong></label>
                    <div class="input-group input-group-lg">
                        <div class="input-group-prepend">
                            <span class="input-group-text">KES</span>
                        </div>
                        <input type="number" class="form-control form-control-lg" id="payment_amount" 
                               name="amount" step="0.01" min="1" max="{{ $paymentLink->amount }}"
                               value="{{ $paymentLink->amount }}" required>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> You can pay partially. Maximum: KES {{ number_format($paymentLink->amount, 2) }}
                    </small>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#payment_amount').val({{ $paymentLink->amount }})">
                            Pay Full Amount
                        </button>
                        @if($paymentLink->amount >= 1000)
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="$('#payment_amount').val({{ $paymentLink->amount / 2 }})">
                            Pay Half
                        </button>
                        @endif
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label for="phone_number"><strong>Your M-PESA Phone Number</strong></label>
                    <div class="input-group input-group-lg">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        </div>
                        <input type="tel" class="form-control form-control-lg" id="phone_number" 
                               placeholder="e.g., 0712345678" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="editPhoneBtn" title="Edit phone number">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> You can change the phone number if paying from a different M-PESA account
                    </small>
                </div>

                <button type="submit" class="btn btn-pay" id="payBtn">
                    <i class="fas fa-lock"></i> PAY NOW WITH M-PESA
                </button>
            </form>

            <div id="statusMessage"></div>

            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-shield-alt"></i> Secure M-PESA Payment Gateway<br>
                    Your payment is processed securely by Safaricom
                </small>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Pre-fill phone number from URL parameter if available
        const urlParams = new URLSearchParams(window.location.search);
        const prefilledPhone = urlParams.get('phone');
        if (prefilledPhone) {
            $('#phone_number').val(prefilledPhone);
        }

        // Edit phone button
        $('#editPhoneBtn').on('click', function() {
            $('#phone_number').prop('readonly', false).focus().select();
        });

        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            
            var phoneNumber = $('#phone_number').val().trim();
            var btn = $('#payBtn');
            var statusDiv = $('#statusMessage');
            
            if (!phoneNumber) {
                showStatus('error', 'Please enter your phone number');
                return;
            }

            // Disable button and show loading
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            statusDiv.hide();

            // Get payment amount
            var paymentAmount = $('#payment_amount').val();
            
            if (!paymentAmount || paymentAmount <= 0) {
                showStatus('error', 'Please enter a valid payment amount');
                return;
            }

            // Submit payment request
            $.ajax({
                url: '{{ route("payment.link.process", $paymentLink->hashed_id) }}',
                method: 'POST',
                data: {
                    phone_number: phoneNumber,
                    amount: paymentAmount,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showStatus('success', 
                            '<i class="fas fa-check-circle"></i> <strong>Payment request sent!</strong><br>' +
                            'Please check your phone and enter your M-PESA PIN to complete the payment.<br>' +
                            '<small>This page will refresh automatically once payment is confirmed.</small>'
                        );
                        
                        // Poll for payment confirmation
                        var pollInterval = setInterval(function() {
                            // Reload page to check if payment completed
                            location.reload();
                        }, 5000);
                        
                        // Stop polling after 2 minutes
                        setTimeout(function() {
                            clearInterval(pollInterval);
                            btn.prop('disabled', false);
                            btn.html('<i class="fas fa-lock"></i> PAY NOW WITH M-PESA');
                            showStatus('warning', 'Payment pending. Please try again if you did not complete the payment.');
                        }, 120000);
                    } else {
                        btn.prop('disabled', false);
                        btn.html('<i class="fas fa-lock"></i> PAY NOW WITH M-PESA');
                        showStatus('error', response.message || 'Payment initiation failed. Please try again.');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-lock"></i> PAY NOW WITH M-PESA');
                    
                    var errorMsg = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showStatus('error', errorMsg);
                }
            });
        });

        function showStatus(type, message) {
            var statusDiv = $('#statusMessage');
            var bgClass = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
            
            statusDiv.removeClass('alert-success alert-warning alert-danger');
            statusDiv.addClass('alert ' + bgClass);
            statusDiv.html(message);
            statusDiv.fadeIn();
        }
    });
    </script>
</body>
</html>

