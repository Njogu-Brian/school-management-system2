@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Prompt Parent to Pay (STK Push)',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Send M-PESA STK Push payment request to parents',
        'actions' => '<a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <div class="row g-4">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-send me-2"></i>
                        Initiate M-PESA STK Push Payment
                    </h5>
                </div>
                <div class="finance-card-body">
                    @includeIf('finance.invoices.partials.alerts')
                    
                    <!-- Success Alert -->
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Success!</strong> {{ session('success') }}
                                @if(session('transaction_id'))
                                    <div class="mt-2">
                                        <small class="d-block">
                                            <strong>Transaction ID:</strong> #{{ session('transaction_id') }}
                                        </small>
                                        @if(session('checkout_request_id'))
                                        <small class="d-block">
                                            <strong>Checkout Request ID:</strong> {{ session('checkout_request_id') }}
                                        </small>
                                        @endif
                                        @if(session('transaction_status'))
                                        <small class="d-block">
                                            <strong>Status:</strong> 
                                            <span class="badge bg-warning text-dark">{{ ucfirst(session('transaction_status')) }}</span>
                                        </small>
                                        @endif
                                        <div class="mt-2">
                                            <a href="{{ route('finance.mpesa.waiting', session('transaction_id')) }}" class="btn btn-sm btn-success">
                                                <i class="bi bi-eye"></i> View Transaction Status
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                    @endif

                    <!-- Error Alert -->
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Error!</strong>
                                <p class="mb-2">{{ session('error') }}</p>
                                @if(str_contains(session('error'), 'authentication') || str_contains(session('error'), 'configuration'))
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-info-circle"></i> <strong>Possible causes:</strong>
                                    </small>
                                    <ul class="small text-muted mb-0 ps-3">
                                        <li>IP address not whitelisted in Safaricom Daraja portal</li>
                                        <li>M-PESA credentials mismatch</li>
                                        <li>App not approved for production environment</li>
                                    </ul>
                                    <small class="text-muted d-block mt-2">
                                        Please contact your system administrator to verify M-PESA configuration.
                                    </small>
                                </div>
                                @else
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i> Please check the information above and try again.
                                </small>
                                @endif
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                    @endif

                    <!-- M-PESA Configuration Status -->
                    @php
                        $configValidation = app(\App\Services\PaymentGateways\MpesaGateway::class)->validateCredentials();
                    @endphp
                    @if(!$configValidation['valid'])
                    <div class="alert alert-warning border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>M-PESA Configuration Issues Detected</strong>
                                @if(!empty($configValidation['issues']))
                                <ul class="mb-2 mt-2">
                                    @foreach($configValidation['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                                @endif
                                @if(!empty($configValidation['recommendations']))
                                <div class="mt-2">
                                    <strong>Action Required:</strong>
                                    <ul class="mb-0">
                                        @foreach($configValidation['recommendations'] as $rec)
                                        <li>{{ $rec }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Info Alert -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>How it works:</strong> When you submit this form, the parent will receive an STK push prompt on their phone to enter their M-PESA PIN and complete the payment.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuration Summary (Non-sensitive) -->
                    <div class="alert alert-light border mb-4">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <strong>Environment:</strong> 
                                <span class="badge {{ $configValidation['environment'] === 'production' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ strtoupper($configValidation['environment'] ?? 'Not Set') }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Shortcode:</strong> 
                                <code>{{ $configValidation['shortcode'] ?? 'Not Set' }}</code>
                            </div>
                            <div class="col-md-6">
                                <strong>OAuth URL:</strong> 
                                <code class="small">{{ $configValidation['oauth_url'] ?? 'N/A' }}</code>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> 
                                @if($configValidation['valid'] ?? false)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Valid</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Issues Found</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.mpesa.prompt-payment') }}" method="POST" id="promptPaymentForm">
                        @csrf
                        
                        <!-- Student Selection with Live Search -->
                        <div class="mb-4">
                            <label class="finance-form-label">
                                Student <span class="text-danger">*</span>
                            </label>
                            @include('partials.student_live_search', [
                                'hiddenInputId' => 'student_id',
                                'displayInputId' => 'studentSearchDisplay',
                                'resultsId' => 'studentSearchResults',
                                'placeholder' => 'Type name or admission #',
                                'initialLabel' => $student ? $student->full_name . ' (' . $student->admission_number . ')' : ''
                            ])
                            @error('student_id')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone Number Selection -->
                        <div class="mb-4" id="phoneSelectionGroup" style="display: {{ $student ? 'block' : 'none' }};">
                            <label for="phone_source" class="finance-form-label">
                                Select Phone Number <span class="text-danger">*</span>
                            </label>
                            <select name="phone_source" id="phone_source" class="finance-form-select">
                                <option value="">-- Select Phone Number --</option>
                                @if($student && $student->family)
                                    @if($student->family->father_phone)
                                        <option value="father" data-phone="{{ $student->family->father_phone }}">
                                            Father's Phone - {{ $student->family->father_phone }}
                                            @if($student->family->father_name)
                                                ({{ $student->family->father_name }})
                                            @endif
                                        </option>
                                    @endif
                                    @if($student->family->mother_phone)
                                        <option value="mother" data-phone="{{ $student->family->mother_phone }}">
                                            Mother's Phone - {{ $student->family->mother_phone }}
                                            @if($student->family->mother_name)
                                                ({{ $student->family->mother_name }})
                                            @endif
                                        </option>
                                    @endif
                                    @if($student->family->phone && $student->family->phone != $student->family->father_phone && $student->family->phone != $student->family->mother_phone)
                                        <option value="primary" data-phone="{{ $student->family->phone }}">
                                            Primary Phone - {{ $student->family->phone }}
                                        </option>
                                    @endif
                                @endif
                                <option value="custom">Enter Different Number</option>
                            </select>
                            <small class="text-muted">Select whose phone number to send payment request to</small>
                        </div>

                        <!-- Phone Number Input -->
                        <div class="mb-4" id="phone_number_group">
                            <label for="phone_number" class="finance-form-label">
                                Phone Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text finance-input-group-text">
                                    <i class="bi bi-phone"></i>
                                </span>
                                <input type="text" name="phone_number" id="phone_number" class="finance-form-control" 
                                       placeholder="e.g., 0712345678 or 254712345678" required>
                            </div>
                            <small class="text-muted">Kenyan mobile number (Safaricom, Airtel, etc.)</small>
                            @error('phone_number')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Invoice Selection (Optional) -->
                        <div class="mb-4" id="invoiceSelectionGroup" style="display: {{ $student ? 'block' : 'none' }};">
                            <label for="invoice_id" class="finance-form-label">Invoice (Optional)</label>
                            <select name="invoice_id" id="invoice_id" class="finance-form-select">
                                <option value="">-- Select Invoice (or leave blank) --</option>
                                @if($invoice)
                                    <option value="{{ $invoice->id }}" selected>
                                        {{ $invoice->invoice_number }} - Balance: KES {{ number_format($invoice->balance, 2) }}
                                    </option>
                                @endif
                            </select>
                            <small class="text-muted">If selected, payment will be allocated to this invoice</small>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <label for="amount" class="finance-form-label">
                                Amount (KES) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text finance-input-group-text">KES</span>
                                <input type="number" name="amount" id="amount" class="finance-form-control" 
                                       step="0.01" min="1" placeholder="0.00" required
                                       value="{{ $invoice ? $invoice->balance : old('amount') }}">
                            </div>
                            @error('amount')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Send Notification Channels -->
                        <div class="mb-4">
                            <label class="finance-form-label">Send Notification Via (Optional)</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="sms" id="sendSMS" checked>
                                    <label class="form-check-label" for="sendSMS">
                                        <i class="bi bi-chat-dots"></i> SMS (RKS_FINANCE)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="email" id="sendEmail">
                                    <label class="form-check-label" for="sendEmail">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="whatsapp" id="sendWhatsApp">
                                    <label class="form-check-label" for="sendWhatsApp">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted">Parent will be notified via selected channels after STK push is sent</small>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="finance-form-label">Notes (Internal)</label>
                            <textarea name="notes" id="notes" class="finance-form-control" rows="3" 
                                      placeholder="Optional notes for reference">{{ old('notes') }}</textarea>
                            <small class="text-muted">These notes are for internal tracking only</small>
                        </div>

                        <!-- Form Actions -->
                        <div class="finance-card-footer">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg">
                                <i class="bi bi-send"></i> Send STK Push
                            </button>
                            <a href="{{ route('finance.mpesa.dashboard') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- How to Use Card -->
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-question-circle me-2"></i>
                        How to Use
                    </h5>
                </div>
                <div class="finance-card-body">
                    <ol class="ps-3 mb-3">
                        <li class="mb-2">Select the student</li>
                        <li class="mb-2">Choose parent's M-PESA phone number</li>
                        <li class="mb-2">Optionally select an invoice</li>
                        <li class="mb-2">Enter the amount to collect</li>
                        <li class="mb-2">Choose notification channels</li>
                        <li class="mb-2">Click "Send STK Push"</li>
                    </ol>
                    <hr class="my-3">
                    <h6 class="mb-2">
                        <i class="bi bi-phone me-2"></i>
                        What happens next?
                    </h6>
                    <p class="small text-muted mb-0">
                        The parent will receive a pop-up on their phone asking them to enter their M-PESA PIN to authorize the payment. Once authorized, the payment will be processed automatically.
                    </p>
                </div>
            </div>

            <!-- Student Info Card -->
            <div class="finance-card finance-animate" id="studentInfoCard" style="display: {{ $student ? 'block' : 'none' }};">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-person me-2"></i>
                        Student Info
                    </h5>
                </div>
                <div class="finance-card-body" id="studentInfoBody">
                    @if($student)
                    <div class="mb-2">
                        <strong>Name:</strong> 
                        <span class="text-muted">{{ $student->first_name }} {{ $student->last_name }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Admission No:</strong>
                        <span class="text-muted">{{ $student->admission_number }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Class:</strong>
                        <span class="text-muted">{{ $student->classroom->name ?? 'N/A' }}</span>
                    </div>
                    @if($student->family)
                        <div class="mb-0">
                            <strong>Parent Phone:</strong>
                            <span class="text-muted">{{ $student->family->phone ?? 'N/A' }}</span>
                        </div>
                    @endif
                    @endif
                </div>
            </div>

            <!-- Recent STK Push Requests -->
            @if($student && $recentTransactions->count() > 0)
            <div class="finance-card finance-animate mt-4" id="recentTransactionsCard">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent STK Push Requests
                    </h5>
                </div>
                <div class="finance-card-body">
                    <div class="list-group list-group-flush">
                        @foreach($recentTransactions as $transaction)
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="d-block">
                                        KES {{ number_format($transaction->amount, 2) }}
                                    </strong>
                                    <small class="text-muted">
                                        {{ $transaction->created_at->format('M d, Y H:i') }}
                                    </small>
                                </div>
                                <div>
                                    @if($transaction->status === 'completed')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Completed
                                        </span>
                                    @elseif($transaction->status === 'processing' || $transaction->status === 'pending')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-hourglass-split"></i> Processing
                                        </span>
                                    @elseif($transaction->status === 'failed' || $transaction->status === 'cancelled')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle"></i> Failed
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($transaction->invoice)
                                <small class="text-muted d-block">
                                    Invoice: {{ $transaction->invoice->invoice_number }}
                                </small>
                            @endif
                            @if($transaction->failure_reason)
                                <small class="text-danger d-block mt-1">
                                    <i class="bi bi-exclamation-triangle"></i> {{ $transaction->failure_reason }}
                                </small>
                            @endif
                            @if($transaction->status === 'completed' && $transaction->paid_at)
                                <small class="text-success d-block mt-1">
                                    <i class="bi bi-check-circle"></i> Paid at {{ $transaction->paid_at->format('M d, Y H:i') }}
                                </small>
                            @endif
                            @if(in_array($transaction->status, ['pending', 'processing']))
                                <div class="mt-2">
                                    <a href="{{ route('finance.mpesa.waiting', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-right-circle"></i> Check Status
                                    </a>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Watch for student selection from live search
    $(document).on('studentSelected', function(e, student) {
        if (student && student.id) {
            loadStudentData(student.id);
        }
    });

    // If student is pre-selected, trigger load
    @if($student)
        loadStudentData({{ $student->id }});
    @endif

    // Load student data function
    function loadStudentData(studentId) {
        $('#phoneSelectionGroup').show();
        $('#invoiceSelectionGroup').show();
        $('#studentInfoCard').show();

        // Load student details
        $.get('/api/students/' + studentId, function(student) {
            // Update student info card
            let infoHtml = `
                <div class="mb-2"><strong>Name:</strong> <span class="text-muted">${student.first_name} ${student.last_name}</span></div>
                <div class="mb-2"><strong>Admission No:</strong> <span class="text-muted">${student.admission_number}</span></div>
                <div class="mb-2"><strong>Class:</strong> <span class="text-muted">${student.classroom?.name || 'N/A'}</span></div>
            `;
            if (student.family && student.family.phone) {
                infoHtml += `<div class="mb-0"><strong>Parent Phone:</strong> <span class="text-muted">${student.family.phone}</span></div>`;
            }
            $('#studentInfoBody').html(infoHtml);

            // Load phone numbers
            let phoneSelect = $('#phone_source');
            phoneSelect.empty();
            phoneSelect.append('<option value="">-- Select Phone Number --</option>');
            
            if (student.family) {
                if (student.family.father_phone) {
                    let fatherName = student.family.father_name ? ` (${student.family.father_name})` : '';
                    phoneSelect.append(`<option value="father" data-phone="${student.family.father_phone}">Father's Phone - ${student.family.father_phone}${fatherName}</option>`);
                }
                if (student.family.mother_phone) {
                    let motherName = student.family.mother_name ? ` (${student.family.mother_name})` : '';
                    phoneSelect.append(`<option value="mother" data-phone="${student.family.mother_phone}">Mother's Phone - ${student.family.mother_phone}${motherName}</option>`);
                }
                if (student.family.phone && student.family.phone != student.family.father_phone && student.family.phone != student.family.mother_phone) {
                    phoneSelect.append(`<option value="primary" data-phone="${student.family.phone}">Primary Phone - ${student.family.phone}</option>`);
                }
            }
            
            phoneSelect.append('<option value="custom">Enter Different Number</option>');
            
            // Auto-select first available phone
            if (phoneSelect.find('option').length > 2) {
                phoneSelect.find('option:eq(1)').prop('selected', true).trigger('change');
            }

            // Load invoices
            $.get('/api/students/' + studentId + '/invoices', function(invoices) {
                let invoiceSelect = $('#invoice_id');
                invoiceSelect.empty();
                invoiceSelect.append('<option value="">-- Select Invoice (or leave blank) --</option>');
                
                invoices.forEach(function(invoice) {
                    if (invoice.balance > 0) {
                        invoiceSelect.append(`<option value="${invoice.id}" data-balance="${invoice.balance}">${invoice.invoice_number} - Balance: KES ${parseFloat(invoice.balance).toLocaleString()}</option>`);
                    }
                });
            });
        });
    }

    // Handle phone source selection
    $('#phone_source').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let phone = selectedOption.data('phone');
        
        if ($(this).val() === 'custom' || $(this).val() === '') {
            $('#phone_number').val('').prop('readonly', false).focus();
        } else if (phone) {
            $('#phone_number').val(phone).prop('readonly', false);
        }
    });

    // Auto-fill amount when invoice is selected
    $('#invoice_id').on('change', function() {
        let selected = $(this).find('option:selected');
        let balance = selected.data('balance');
        if (balance) {
            $('#amount').val(parseFloat(balance).toFixed(2));
        }
    });

    // Form submission
    $('#promptPaymentForm').on('submit', function(e) {
        let btn = $(this).find('button[type="submit"]');
        let form = $(this);
        
        // Disable button and show loading state
        btn.prop('disabled', true);
        btn.html('<i class="bi bi-hourglass-split"></i> Sending STK Push...');
        
        // Clear any previous alerts
        $('.alert').alert('close');
        
        // Scroll to top to show any messages
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Re-enable button after 5 seconds (in case of network issues)
        setTimeout(function() {
            if (btn.prop('disabled')) {
                btn.prop('disabled', false);
                btn.html('<i class="bi bi-send"></i> Send STK Push');
            }
        }, 5000);
    });
    
    // Auto-scroll to top if there's a success/error message
    @if(session('success') || session('error'))
        window.scrollTo({ top: 0, behavior: 'smooth' });
    @endif
});
</script>
@endsection
