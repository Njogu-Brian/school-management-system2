@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff Advances</div>
                <h1 class="mb-1">New Staff Advance</h1>
                <p class="text-muted mb-0">Create a new advance loan for staff.</p>
            </div>
            <a href="{{ route('hr.payroll.advances.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Advances
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Advance Details</h5>
                    <p class="text-muted small mb-0">Capture loan amount, repayment method, and dates.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields marked *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.advances.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select @error('staff_id') is-invalid @enderror" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('staff_id')==$s->id)>{{ $s->name }} ({{ $s->staff_id }})</option>
                            @endforeach
                        </select>
                        @error('staff_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Advance Amount (Ksh) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Advance Date <span class="text-danger">*</span></label>
                        <input type="date" name="advance_date" class="form-control @error('advance_date') is-invalid @enderror" value="{{ old('advance_date', date('Y-m-d')) }}" required>
                        @error('advance_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose') }}" placeholder="e.g., Emergency loan">
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Repayment Method <span class="text-danger">*</span></label>
                        <select name="repayment_method" id="repayment_method" class="form-select @error('repayment_method') is-invalid @enderror" required>
                            <option value="lump_sum" @selected(old('repayment_method')==='lump_sum')>Lump Sum</option>
                            <option value="installments" @selected(old('repayment_method')==='installments')>Installments</option>
                            <option value="monthly_deduction" @selected(old('repayment_method')==='monthly_deduction')>Monthly Deduction</option>
                        </select>
                        @error('repayment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 d-none" id="installment_count_field">
                        <label class="form-label">Number of Installments</label>
                        <input type="number" name="installment_count" min="1" class="form-control @error('installment_count') is-invalid @enderror" value="{{ old('installment_count') }}">
                        @error('installment_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 d-none" id="monthly_deduction_field">
                        <label class="form-label">Monthly Deduction Amount (Ksh)</label>
                        <input type="number" name="monthly_deduction_amount" step="0.01" min="0.01" class="form-control @error('monthly_deduction_amount') is-invalid @enderror" value="{{ old('monthly_deduction_amount') }}">
                        @error('monthly_deduction_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Expected Completion Date</label>
                        <input type="date" name="expected_completion_date" class="form-control @error('expected_completion_date') is-invalid @enderror" value="{{ old('expected_completion_date') }}">
                        @error('expected_completion_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Short description for this advance">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes (optional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('hr.payroll.advances.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Advance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('repayment_method').addEventListener('change', function() {
  const method = this.value;
  const installmentField = document.getElementById('installment_count_field');
  const monthlyField = document.getElementById('monthly_deduction_field');

  installmentField.classList.toggle('d-none', method !== 'installments');
  monthlyField.classList.toggle('d-none', method !== 'monthly_deduction');

  const installmentInput = installmentField.querySelector('input');
  const monthlyInput = monthlyField.querySelector('input');
  installmentInput.required = method === 'installments';
  monthlyInput.required = method === 'monthly_deduction';
});

document.getElementById('repayment_method').dispatchEvent(new Event('change'));
</script>
@endsection

