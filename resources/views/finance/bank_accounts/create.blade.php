@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Create Bank Account',
        'icon' => 'bi bi-plus-circle',
        'subtitle' => 'Add a new bank account'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Bank Account Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.bank-accounts.store') }}">
                @csrf
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="finance-form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="name" 
                               class="finance-form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name') }}" 
                               placeholder="e.g., Main School Account"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Account Number <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="account_number" 
                               class="finance-form-control @error('account_number') is-invalid @enderror" 
                               value="{{ old('account_number') }}" 
                               required>
                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="bank_name" 
                               class="finance-form-control @error('bank_name') is-invalid @enderror" 
                               value="{{ old('bank_name') }}" 
                               placeholder="e.g., Equity Bank, KCB, Co-operative Bank"
                               required>
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Branch</label>
                        <input type="text" 
                               name="branch" 
                               class="finance-form-control" 
                               value="{{ old('branch') }}" 
                               placeholder="e.g., Westlands Branch">
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" class="finance-form-select @error('account_type') is-invalid @enderror" required>
                            <option value="current" {{ old('account_type') == 'current' ? 'selected' : '' }}>Current Account</option>
                            <option value="savings" {{ old('account_type') == 'savings' ? 'selected' : '' }}>Savings Account</option>
                            <option value="deposit" {{ old('account_type') == 'deposit' ? 'selected' : '' }}>Deposit Account</option>
                            <option value="other" {{ old('account_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('account_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Currency</label>
                        <input type="text" 
                               name="currency" 
                               class="finance-form-control" 
                               value="{{ old('currency', 'KES') }}" 
                               maxlength="3"
                               placeholder="KES">
                        <small class="form-text text-muted">ISO currency code (e.g., KES, USD, EUR)</small>
                    </div>

                    <div class="col-md-12">
                        <label class="finance-form-label">Notes</label>
                        <textarea name="notes" 
                                  class="finance-form-control" 
                                  rows="3"
                                  placeholder="Additional notes about this account">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   id="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                            <small class="form-text text-muted d-block">Only active bank accounts will appear in payment forms</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.bank-accounts.index') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-check-circle"></i> Create Bank Account
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
@endsection

