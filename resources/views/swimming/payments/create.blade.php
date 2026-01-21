@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Create Swimming Payment',
        'icon' => 'bi bi-water',
        'subtitle' => 'Record a swimming payment and credit student wallet'
    ])

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('swimming.payments.store') }}" method="POST" id="paymentForm">
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
                                    'initialLabel' => old('student_id') 
                                        ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') 
                                        : ($student ? $student->full_name . ' (' . $student->admission_number . ')' : '')
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
                                    @foreach($paymentMethods as $method)
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
                                <label class="finance-form-label">Bank Account</label>
                                <select name="bank_account_id" class="finance-form-select">
                                    <option value="">-- Select Account --</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->account_number }})
                                        </option>
                                    @endforeach
                                </select>
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
                                    <option value="student" {{ old('payer_type') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="other" {{ old('payer_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Transaction Code</label>
                                <input type="text" 
                                       name="transaction_code" 
                                       class="finance-form-control @error('transaction_code') is-invalid @enderror" 
                                       value="{{ old('transaction_code') }}" 
                                       placeholder="Enter transaction code (e.g., M-Pesa code, bank reference)">
                                <small class="form-text text-muted">Optional. Leave blank to auto-generate.</small>
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

                            <!-- Payment Sharing Section -->
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="share_with_siblings" 
                                           value="1" 
                                           id="share_with_siblings"
                                           {{ old('share_with_siblings') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="share_with_siblings">
                                        Share payment among siblings
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12" id="sibling_allocation_section" style="display: none;">
                                <div class="finance-card" style="background: #f8f9fa; border: 2px dashed #dee2e6;">
                                    <div class="finance-card-header">
                                        <i class="bi bi-people me-2"></i> Sibling Allocation
                                    </div>
                                    <div class="finance-card-body">
                                        <div id="sibling_allocation_fields"></div>
                                        <small class="text-muted">Total allocated amount must equal payment amount.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-header">
                        <i class="bi bi-info-circle"></i> Information
                    </div>
                    <div class="finance-card-body">
                        <p class="text-muted">
                            <i class="bi bi-water"></i> This payment will be credited directly to the student's swimming wallet.
                        </p>
                        <p class="text-muted">
                            <i class="bi bi-cash-stack"></i> All payment methods are supported (Cash, M-Pesa, Bank Transfer, etc.).
                        </p>
                        <p class="text-muted">
                            <i class="bi bi-people"></i> You can share the payment among siblings if needed.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <button type="submit" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-check-circle"></i> Create Payment & Credit Wallet
                </button>
                <a href="{{ route('swimming.wallets.index') }}" class="btn btn-finance btn-finance-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shareCheckbox = document.getElementById('share_with_siblings');
    const allocationSection = document.getElementById('sibling_allocation_section');
    const studentInput = document.getElementById('student_id');
    
    shareCheckbox.addEventListener('change', function() {
        if (this.checked) {
            allocationSection.style.display = 'block';
            loadSiblings();
        } else {
            allocationSection.style.display = 'none';
            document.getElementById('sibling_allocation_fields').innerHTML = '';
        }
    });
    
    function loadSiblings() {
        const studentId = studentInput.value;
        if (!studentId) {
            alert('Please select a student first');
            shareCheckbox.checked = false;
            allocationSection.style.display = 'none';
            return;
        }
        
        fetch(`{{ route('swimming.payments.siblings', ['student' => ':id']) }}`.replace(':id', studentId))
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('sibling_allocation_fields');
                container.innerHTML = '';
                
                // Add main student first - always show even if no siblings
                if (data.student) {
                    const div = document.createElement('div');
                    div.className = 'mb-3';
                    div.innerHTML = `
                        <label class="finance-form-label">
                            ${data.student.full_name} (${data.student.admission_number})
                            <br><small class="text-danger">Swimming Balance: Ksh ${parseFloat(data.student.swimming_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="hidden" name="sibling_allocations[0][student_id]" value="${data.student.id}">
                            <input type="number" 
                                   name="sibling_allocations[0][amount]" 
                                   step="0.01" 
                                   min="0" 
                                   class="form-control sibling-amount" 
                                   value="0"
                                   onchange="updateTotal()">
                        </div>
                    `;
                    container.appendChild(div);
                }
                
                // Add siblings if they exist
                if (data.siblings && data.siblings.length > 0) {
                    let index = data.student ? 1 : 0;
                    data.siblings.forEach(sibling => {
                        const div = document.createElement('div');
                        div.className = 'mb-3';
                        div.innerHTML = `
                            <label class="finance-form-label">
                                ${sibling.full_name} (${sibling.admission_number})
                                <br><small class="text-danger">Swimming Balance: Ksh ${parseFloat(sibling.swimming_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="hidden" name="sibling_allocations[${index}][student_id]" value="${sibling.id}">
                                <input type="number" 
                                       name="sibling_allocations[${index}][amount]" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control sibling-amount" 
                                       value="0"
                                       onchange="updateTotal()">
                            </div>
                        `;
                        container.appendChild(div);
                        index++;
                    });
                } else if (!data.student) {
                    container.innerHTML = '<p class="text-muted">No siblings found for this student.</p>';
                } else {
                    // Show message if main student exists but no siblings
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'alert alert-info mt-2 mb-0';
                    messageDiv.innerHTML = '<small><i class="bi bi-info-circle"></i> No siblings found. You can still allocate the full amount to this student.</small>';
                    container.appendChild(messageDiv);
                }
            })
            .catch(error => {
                console.error('Error loading siblings:', error);
            });
    }
    
    window.updateTotal = function() {
        const totalAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
        const siblingInputs = document.querySelectorAll('.sibling-amount');
        let allocatedTotal = 0;
        
        siblingInputs.forEach(input => {
            allocatedTotal += parseFloat(input.value) || 0;
        });
        
        const difference = totalAmount - allocatedTotal;
        if (Math.abs(difference) > 0.01) {
            // Show warning if totals don't match
            console.log('Total mismatch:', difference);
        }
    };
});
</script>
@endsection
