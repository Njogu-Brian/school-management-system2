@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Edit Payment Method',
        'icon' => 'bi bi-pencil',
        'subtitle' => 'Update payment method details'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Payment Method Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.payment-methods.update', $paymentMethod) }}">
                @csrf
                @method('PUT')
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="finance-form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="name" 
                               class="finance-form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $paymentMethod->name) }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="code" 
                               class="finance-form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $paymentMethod->code) }}" 
                               required>
                        <small class="form-text text-muted">Unique code identifier (uppercase, no spaces)</small>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="finance-form-select @error('bank_account_id') is-invalid @enderror" required>
                            <option value="">-- Select Bank Account --</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('bank_account_id', $paymentMethod->bank_account_id) == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} - {{ $account->account_number }} ({{ $account->bank_name }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">A bank account is required for all payment methods</small>
                        @error('bank_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($bankAccounts->isEmpty())
                            <div class="alert alert-warning mt-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>No bank accounts found!</strong> 
                                <a href="{{ route('finance.bank-accounts.create') }}" class="alert-link">Create a bank account first</a> before setting up payment methods.
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="finance-form-label">Display Order</label>
                        <input type="number" 
                               name="display_order" 
                               class="finance-form-control" 
                               value="{{ old('display_order', $paymentMethod->display_order ?? 0) }}" 
                               min="0">
                        <small class="form-text text-muted">Lower numbers appear first in dropdowns</small>
                    </div>

                    <div class="col-md-12">
                        <label class="finance-form-label">Description</label>
                        <textarea name="description" 
                                  class="finance-form-control" 
                                  rows="3">{{ old('description', $paymentMethod->description) }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="requires_reference" 
                                   value="1" 
                                   id="requires_reference"
                                   {{ old('requires_reference', $paymentMethod->requires_reference) ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_reference">
                                Requires Reference Number
                            </label>
                            <small class="form-text text-muted d-block">Check if this payment method requires a transaction reference</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_online" 
                                   value="1" 
                                   id="is_online"
                                   {{ old('is_online', $paymentMethod->is_online) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_online">
                                Online Payment Method
                            </label>
                            <small class="form-text text-muted d-block">Check if this is an online payment method</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   id="is_active"
                                   {{ old('is_active', $paymentMethod->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                            <small class="form-text text-muted d-block">Only active payment methods will appear in payment forms</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.payment-methods.index') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-check-circle"></i> Update Payment Method
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

