@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 p-3">
        <h3 class="mb-0">
            <i class="bi bi-plus-circle"></i> Create Fee Discount
        </h3>
    </div>

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.discounts.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <h5 class="mb-0">Discount Details</h5>
                    </div>
                    <div class="finance-card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Template Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g., Sibling Discount 5% for 2nd Child" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Give this discount template a descriptive name</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select @error('discount_type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="sibling" {{ old('discount_type') == 'sibling' ? 'selected' : '' }}>Sibling Discount</option>
                                    <option value="referral" {{ old('discount_type') == 'referral' ? 'selected' : '' }}>Referral Discount</option>
                                    <option value="early_repayment" {{ old('discount_type') == 'early_repayment' ? 'selected' : '' }}>Early Repayment</option>
                                    <option value="transport" {{ old('discount_type') == 'transport' ? 'selected' : '' }}>Transport Discount</option>
                                    <option value="manual" {{ old('discount_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                                    <option value="other" {{ old('discount_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('discount_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Scope <span class="text-danger">*</span></label>
                                <select name="scope" id="scope" class="form-select @error('scope') is-invalid @enderror" required>
                                    <option value="">-- Select Scope --</option>
                                    <option value="votehead" {{ old('scope') == 'votehead' ? 'selected' : '' }}>Votehead</option>
                                    <option value="invoice" {{ old('scope') == 'invoice' ? 'selected' : '' }}>Invoice</option>
                                    <option value="student" {{ old('scope') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="family" {{ old('scope') == 'family' ? 'selected' : '' }}>Family</option>
                                </select>
                                @error('scope')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Discount Amount Type <span class="text-danger">*</span></label>
                                <select name="type" id="discount_amount_type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed_amount" {{ old('type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount (Ksh)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" id="value_label">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="value" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control @error('value') is-invalid @enderror" 
                                           value="{{ old('value') }}" 
                                           required>
                                    <span class="input-group-text" id="value_suffix">%</span>
                                </div>
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Frequency <span class="text-danger">*</span></label>
                                <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                                    <option value="termly" {{ old('frequency') == 'termly' ? 'selected' : '' }}>Termly</option>
                                    <option value="yearly" {{ old('frequency') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                    <option value="once" {{ old('frequency') == 'once' ? 'selected' : '' }}>Once</option>
                                    <option value="manual" {{ old('frequency') == 'manual' ? 'selected' : '' }}>Manual</option>
                                </select>
                                @error('frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       name="start_date" 
                                       class="form-control @error('start_date') is-invalid @enderror" 
                                       value="{{ old('start_date', date('Y-m-d')) }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" 
                                       name="end_date" 
                                       class="form-control @error('end_date') is-invalid @enderror" 
                                       value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank for no expiry</small>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="reason" 
                                       class="form-control @error('reason') is-invalid @enderror" 
                                       value="{{ old('reason') }}" 
                                       placeholder="e.g., Sibling discount for 3 children" 
                                       required>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="auto_approve" 
                                           value="1" 
                                           id="auto_approve"
                                           {{ old('auto_approve') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_approve">
                                        Auto-approve discount
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <h5 class="mb-0">Info</h5>
                    </div>
                    <div class="finance-card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> This form creates a discount template. To allocate this discount to students, use the "Allocate Discount" option after creating the template.
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-check-circle"></i> Create Template
                    </button>
                    <a href="{{ route('finance.discounts.templates.index') }}" class="btn btn-finance btn-finance-outline">
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
    const discountType = document.getElementById('discount_amount_type');
    const valueSuffix = document.getElementById('value_suffix');

    // Scope selector is now just for information, no UI changes needed

    // Handle discount type change
    discountType.addEventListener('change', function() {
        valueSuffix.textContent = this.value === 'percentage' ? '%' : 'Ksh';
    });

    // Trigger on page load
    discountType.dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection

