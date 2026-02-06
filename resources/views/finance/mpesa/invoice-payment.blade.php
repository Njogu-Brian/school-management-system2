<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pay Invoice - M-PESA</title>
    
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
            padding: 20px;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
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
        .invoice-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .amount-display {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 20px 0;
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
        .invoice-items {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        .invoice-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="payment-header">
            <i class="fas fa-file-invoice-dollar"></i>
            <h2>Pay Invoice</h2>
            <p class="mb-0">{{ \App\Models\Setting::get('school_name', 'School') }}</p>
        </div>
        
        <div class="payment-body">
            <div class="invoice-details">
                <h5 class="mb-3"><i class="fas fa-user"></i> Student Information</h5>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $invoice->student->full_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admission No:</span>
                    <span class="detail-value">{{ $invoice->student->admission_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Class:</span>
                    <span class="detail-value">{{ $invoice->student->classroom->name ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="invoice-details">
                <h5 class="mb-3"><i class="fas fa-file-invoice"></i> Invoice Details</h5>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number:</span>
                    <span class="detail-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Academic Year:</span>
                    <span class="detail-value">{{ $invoice->academicYear->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Term:</span>
                    <span class="detail-value">{{ $invoice->term->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">KES {{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Paid Amount:</span>
                    <span class="detail-value text-success">KES {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
            </div>

            <div class="text-center">
                <h5>Outstanding Balance</h5>
                <div class="amount-display">
                    KES {{ number_format($invoice->balance, 2) }}
                </div>
            </div>

            <form id="paymentForm">
                <div class="form-group">
                    <label for="payment_amount"><strong>Payment Amount</strong></label>
                    <div class="input-group input-group-lg">
                        <div class="input-group-prepend">
                            <span class="input-group-text">KES</span>
                        </div>
                        <input type="number" class="form-control form-control-lg" id="payment_amount" 
                               name="amount" step="0.01" min="1" max="{{ $invoice->balance }}"
                               value="{{ $invoice->balance }}" required>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> You can pay partially or in full. Maximum: KES {{ number_format($invoice->balance, 2) }}
                    </small>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="$('#payment_amount').val({{ $invoice->balance }})">
                            <i class="fas fa-check-circle"></i> Pay Full Balance
                        </button>
                        @if($invoice->balance >= 1000)
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#payment_amount').val({{ $invoice->balance / 2 }})">
                            Pay Half ({{ number_format($invoice->balance / 2, 2) }})
                        </button>
                        @endif
                        @if($invoice->balance >= 5000)
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="$('#payment_amount').val({{ min(5000, $invoice->balance) }})">
                            Pay KES 5,000
                        </button>
                        @endif
                    </div>
                </div>

                <div class="form-group">
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
            var paymentAmount = $('#payment_amount').val();
            var btn = $('#payBtn');
            var statusDiv = $('#statusMessage');
            
            if (!phoneNumber) {
                showStatus('error', 'Please enter your phone number');
                return;
            }

            if (!paymentAmount || paymentAmount <= 0) {
                showStatus('error', 'Please enter a valid payment amount');
                return;
            }

            if (parseFloat(paymentAmount) > {{ $invoice->balance }}) {
                showStatus('error', 'Payment amount cannot exceed the outstanding balance');
                return;
            }

            // Disable button and show loading
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            statusDiv.hide();

            // Submit payment request
            $.ajax({
                url: '{{ route("invoice.pay.mpesa", $invoice) }}',
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

