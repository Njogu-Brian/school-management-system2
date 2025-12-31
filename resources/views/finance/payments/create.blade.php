@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Record Payment',
        'icon' => 'bi bi-cash-stack',
        'subtitle' => 'Record a new payment for a student'
    ])

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.payments.store') }}" method="POST" id="paymentForm">
        @csrf
        
        <div class="row g-4">
            <div class="col-md-8">
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-header d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i> <span>Payment Information</span>
                    </div>
                    <div class="finance-card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                                @include('partials.student_live_search', [
                                    'hiddenInputId' => 'student_id',
                                    'displayInputId' => 'studentLiveSearch',
                                    'resultsId' => 'studentLiveResults',
                                    'placeholder' => 'Type student name or admission #',
                                    'initialLabel' => old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : ''
                                ])
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Ksh</span>
                                    <input type="number" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0.01" 
                                           class="finance-form-control @error('amount') is-invalid @enderror" 
                                           value="{{ old('amount') }}" 
                                           id="payment_amount"
                                           required>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       name="payment_date" 
                                       class="finance-form-control @error('payment_date') is-invalid @enderror" 
                                       value="{{ old('payment_date', date('Y-m-d')) }}" 
                                       required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="finance-form-select @error('payment_method_id') is-invalid @enderror" required>
                                    <option value="">-- Select Method --</option>
                                    @foreach(\App\Models\PaymentMethod::where('is_active', true)->get() as $method)
                                        <option value="{{ $method->id }}" {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Payer Name</label>
                                <input type="text" 
                                       name="payer_name" 
                                       class="finance-form-control" 
                                       value="{{ old('payer_name') }}" 
                                       placeholder="Name of person making payment">
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Payer Type</label>
                                <select name="payer_type" class="finance-form-select">
                                    <option value="">-- Select Type --</option>
                                    <option value="parent" {{ old('payer_type') == 'parent' ? 'selected' : '' }}>Parent</option>
                                    <option value="sponsor" {{ old('payer_type') == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                                    <option value="student" {{ old('payer_type') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="other" {{ old('payer_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Transaction Code <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="transaction_code" 
                                       class="finance-form-control @error('transaction_code') is-invalid @enderror" 
                                       value="{{ old('transaction_code') }}" 
                                       placeholder="Enter transaction code (e.g., M-Pesa code, bank reference). This must be unique."
                                       required>
                                <small class="form-text text-muted">This transaction code must be unique. No two payments can share the same code.</small>
                                @error('transaction_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Narration</label>
                                <textarea name="narration" 
                                          class="finance-form-control" 
                                          rows="2" 
                                          placeholder="Additional notes or description">{{ old('narration') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="auto_allocate" 
                                           value="1" 
                                           id="auto_allocate"
                                           checked>
                                    <label class="form-check-label" for="auto_allocate">
                                        Auto-allocate payment to outstanding invoice items
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Sharing Section -->
                            <div class="col-md-12" id="payment_sharing_section" style="display: none;">
                                <div class="finance-card" style="background: #f8f9fa; border: 2px dashed #dee2e6;">
                                    <div class="finance-card-header">
                                        <i class="bi bi-people me-2"></i> Share Payment Among Siblings
                                        <button type="button" class="btn btn-sm btn-outline-secondary float-end" id="toggle_sharing">
                                            <i class="bi bi-x"></i> Cancel Sharing
                                        </button>
                                    </div>
                                    <div class="finance-card-body">
                                        <input type="hidden" name="shared_payment" id="shared_payment" value="0">
                                        <p class="text-muted small mb-3">Distribute the payment amount among siblings. Total must equal payment amount.</p>
                                        <div id="siblings_list"></div>
                                        <div class="mt-3">
                                            <strong>Total Shared: Ksh <span id="total_shared">0.00</span></strong>
                                            <span class="text-danger" id="sharing_error" style="display: none;">Total must equal payment amount!</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-header secondary d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i> <span>Student Balance Info</span>
                    </div>
                    <div class="finance-card-body p-4" id="student_balance_info">
                        <p class="text-muted text-center">Select a student to view balance</p>
                    </div>
                </div>

                <!-- Siblings Section -->
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-3" id="siblings_card" style="display: none;">
                    <div class="finance-card-header secondary d-flex align-items-center gap-2">
                        <i class="bi bi-people"></i> <span>Siblings</span>
                    </div>
                    <div class="finance-card-body p-4" id="siblings_info">
                        <p class="text-muted small">This student has siblings in the system.</p>
                    </div>
                </div>

                <!-- Overpayment Warning -->
                <div class="alert alert-warning mt-3" id="overpayment_warning" style="display: none;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Overpayment Warning!</strong>
                    <p class="mb-0 small" id="overpayment_message"></p>
                    <input type="hidden" name="confirm_overpayment" id="confirm_overpayment" value="0">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="confirm_overpayment_check">
                        <label class="form-check-label small" for="confirm_overpayment_check">
                            I understand the overpayment will be carried forward
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-finance btn-finance-primary" id="submit_btn">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-finance btn-finance-outline">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('student_id');
    const balanceInfo = document.getElementById('student_balance_info');
    const siblingsCard = document.getElementById('siblings_card');
    const siblingsInfo = document.getElementById('siblings_info');
    const paymentAmount = document.getElementById('payment_amount');
    const overpaymentWarning = document.getElementById('overpayment_warning');
    const overpaymentMessage = document.getElementById('overpayment_message');
    const confirmOverpayment = document.getElementById('confirm_overpayment');
    const confirmOverpaymentCheck = document.getElementById('confirm_overpayment_check');
    const paymentSharingSection = document.getElementById('payment_sharing_section');
    const sharedPaymentInput = document.getElementById('shared_payment');
    const siblingsList = document.getElementById('siblings_list');
    const totalSharedSpan = document.getElementById('total_shared');
    const sharingError = document.getElementById('sharing_error');
    const submitBtn = document.getElementById('submit_btn');
    
    let currentStudentData = null;
    let siblings = [];

    studentSelect.addEventListener('change', function() {
        const studentId = this.value;
        if (studentId) {
            fetch(`{{ route('finance.payments.student-info', ['student' => '__ID__']) }}`.replace('__ID__', studentId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    currentStudentData = data;
                    siblings = data.siblings || [];
                    
                    // Update balance info
                    const balance = parseFloat(data.balance.total_balance || 0);
                    const invoiceBalance = parseFloat(data.balance.invoice_balance || 0);
                    const balanceBroughtForward = parseFloat(data.balance.balance_brought_forward || 0);
                    
                    let balanceHtml = `
                        <p><strong>Total Outstanding:</strong></p>
                        <h4 class="text-danger">Ksh ${balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                    `;
                    
                    if (balanceBroughtForward > 0) {
                        balanceHtml += `
                            <hr>
                            <small class="text-muted">
                                <div><strong>Breakdown:</strong></div>
                                <div>Invoice Balance: Ksh ${invoiceBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div>Balance Brought Forward: Ksh ${balanceBroughtForward.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            </small>
                            <hr>
                        `;
                    } else {
                        balanceHtml += '<hr>';
                    }
                    
                    balanceHtml += `
                        <small class="text-muted">
                            Unpaid Invoices: ${data.balance.unpaid_invoices || 0}<br>
                            Partial Payments: ${data.balance.partial_invoices || 0}
                        </small>
                    `;
                    
                    balanceInfo.innerHTML = balanceHtml;
                    
                    // Show siblings if they exist
                    if (siblings.length > 0) {
                        let siblingsHtml = '<div class="list-group">';
                        siblings.forEach(sibling => {
                            siblingsHtml += `
                                <div class="list-group-item" style="opacity: 0.6; background: #f8f9fa;">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>${sibling.name}</strong><br>
                                            <small class="text-muted">${sibling.admission_number}</small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">Balance:</small><br>
                                            <strong class="text-danger">Ksh ${parseFloat(sibling.balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        siblingsHtml += '</div>';
                        siblingsInfo.innerHTML = siblingsHtml;
                        siblingsCard.style.display = 'block';
                        
                        // Add share payment button
                        if (!document.getElementById('share_payment_btn')) {
                            const shareBtn = document.createElement('button');
                            shareBtn.type = 'button';
                            shareBtn.className = 'btn btn-sm btn-finance btn-finance-success mt-2';
                            shareBtn.id = 'share_payment_btn';
                            shareBtn.innerHTML = '<i class="bi bi-share"></i> Share Payment';
                            shareBtn.onclick = function() {
                                showPaymentSharing();
                            };
                            siblingsInfo.appendChild(shareBtn);
                        }
                    } else {
                        siblingsCard.style.display = 'none';
                    }
                    
                    checkOverpayment();
                })
                .catch(error => {
                    console.error('Error loading balance info:', error);
                    balanceInfo.innerHTML = `
                        <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Unable to load balance info</p>
                        <small class="text-muted">Error: ${error.message || 'Unknown error'}</small>
                    `;
                    siblingsCard.style.display = 'none';
                });
        } else {
            balanceInfo.innerHTML = '<p class="text-muted text-center">Select a student to view balance</p>';
            siblingsCard.style.display = 'none';
            paymentSharingSection.style.display = 'none';
            overpaymentWarning.style.display = 'none';
        }
    });

    paymentAmount.addEventListener('input', function() {
        checkOverpayment();
        updateTotalShared();
    });

    function checkOverpayment() {
        if (!currentStudentData) return;
        
        const amount = parseFloat(paymentAmount.value || 0);
        const balance = parseFloat(currentStudentData.balance.total_balance || 0);
        
        if (amount > balance && amount > 0) {
            const overpayment = amount - balance;
            overpaymentMessage.textContent = `Payment amount (Ksh ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}) exceeds balance (Ksh ${balance.toLocaleString('en-US', {minimumFractionDigits: 2})}). Overpayment of Ksh ${overpayment.toLocaleString('en-US', {minimumFractionDigits: 2})} will be carried forward.`;
            overpaymentWarning.style.display = 'block';
        } else {
            overpaymentWarning.style.display = 'none';
        }
    }

    function showPaymentSharing() {
        paymentSharingSection.style.display = 'block';
        sharedPaymentInput.value = '1';
        
        let html = '';
        siblings.forEach((sibling, index) => {
            html += `
                <div class="mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${sibling.name}</strong><br>
                            <small class="text-muted">${sibling.admission_number} | Balance: Ksh ${parseFloat(sibling.balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
                        </div>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text">Ksh</span>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               class="form-control sibling-amount" 
                               name="shared_amounts[]" 
                               data-sibling-id="${sibling.id}"
                               value="0"
                               oninput="updateTotalShared()">
                        <input type="hidden" name="shared_students[]" value="${sibling.id}">
                    </div>
                </div>
            `;
        });
        siblingsList.innerHTML = html;
        updateTotalShared();
    }

    window.updateTotalShared = function() {
        const amounts = document.querySelectorAll('.sibling-amount');
        let total = 0;
        amounts.forEach(input => {
            total += parseFloat(input.value || 0);
        });
        totalSharedSpan.textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Only disable if payment sharing is active
        if (sharedPaymentInput.value === '1') {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value || 0);
            if (Math.abs(total - paymentAmount) > 0.01) {
                sharingError.style.display = 'inline';
                submitBtn.disabled = true;
            } else {
                sharingError.style.display = 'none';
                submitBtn.disabled = false;
            }
        } else {
            // Payment sharing not active, ensure button is enabled
            sharingError.style.display = 'none';
            submitBtn.disabled = false;
        }
    };

    document.getElementById('toggle_sharing')?.addEventListener('click', function() {
        paymentSharingSection.style.display = 'none';
        sharedPaymentInput.value = '0';
        siblingsList.innerHTML = '';
        updateTotalShared();
    });

    confirmOverpaymentCheck?.addEventListener('change', function() {
        confirmOverpayment.value = this.checked ? '1' : '0';
    });

    // Form submission validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        if (overpaymentWarning.style.display === 'block' && !confirmOverpaymentCheck.checked) {
            e.preventDefault();
            alert('Please confirm that you understand the overpayment will be carried forward.');
            return false;
        }
        
        // Ensure button is enabled before submission
        submitBtn.disabled = false;
    });
    
    // Enable button on form field changes
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('change', function() {
            if (sharedPaymentInput.value !== '1') {
                submitBtn.disabled = false;
            }
        });
    });
});
</script>
@endpush
@endsection
