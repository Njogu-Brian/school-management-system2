@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Record Payment',
        'icon' => 'bi bi-cash-stack',
        'subtitle' => 'Record a new payment for a student'
    ])

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.payments.store') }}" method="POST" id="paymentForm">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="finance-card finance-animate">
                    <div class="finance-card-header">
                        <i class="bi bi-info-circle me-2"></i> Payment Information
                    </div>
                    <div class="finance-card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                                <select name="student_id" id="student_id" class="finance-form-select @error('student_id') is-invalid @enderror" required>
                                    <option value="">-- Search and Select Student --</option>
                                    @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                                        <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
                                        </option>
                                    @endforeach
                                </select>
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
                                <label class="finance-form-label">Bank Account</label>
                                <select name="bank_account_id" class="finance-form-select">
                                    <option value="">-- Select Bank Account --</option>
                                    @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $account)
                                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} - {{ $account->account_number }}
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
                                    <option value="sponsor" {{ old('payer_type') == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                                    <option value="student" {{ old('payer_type') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="other" {{ old('payer_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Reference Number</label>
                                <input type="text" 
                                       name="reference" 
                                       class="finance-form-control" 
                                       value="{{ old('reference') }}" 
                                       placeholder="M-Pesa code, cheque number, etc.">
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Narration/Notes</label>
                                <textarea name="narration" 
                                          class="finance-form-control" 
                                          rows="3" 
                                          placeholder="Additional payment notes">{{ old('narration') }}</textarea>
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="finance-card finance-animate">
                    <div class="finance-card-header secondary">
                        <i class="bi bi-info-circle me-2"></i> Student Balance Info
                    </div>
                    <div class="finance-card-body" id="student_balance_info">
                        <p class="text-muted text-center">Select a student to view balance</p>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-finance btn-finance-primary">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('student_id');
    const balanceInfo = document.getElementById('student_balance_info');

    studentSelect.addEventListener('change', function() {
        const studentId = this.value;
        if (studentId) {
            // Fetch student balance info via AJAX
            fetch(`/api/students/${studentId}/balance`)
                .then(response => response.json())
                .then(data => {
                    balanceInfo.innerHTML = `
                        <p><strong>Total Outstanding:</strong></p>
                        <h4 class="text-danger">Ksh ${parseFloat(data.total_balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                        <hr>
                        <small class="text-muted">
                            Unpaid Invoices: ${data.unpaid_invoices || 0}<br>
                            Partial Payments: ${data.partial_invoices || 0}
                        </small>
                    `;
                })
                .catch(error => {
                    balanceInfo.innerHTML = '<p class="text-muted">Unable to load balance info</p>';
                });
        } else {
            balanceInfo.innerHTML = '<p class="text-muted text-center">Select a student to view balance</p>';
        }
    });
});
</script>
@endpush
@endsection
