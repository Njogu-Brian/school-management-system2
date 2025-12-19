@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Custom Deductions</div>
                <h1 class="mb-1">New Custom Deduction</h1>
                <p class="text-muted mb-0">Create a new custom deduction for staff.</p>
            </div>
            <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Deductions
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Deduction Details</h5>
                    <p class="text-muted small mb-0">Set amount, schedule, and optional advance linkage.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.custom-deductions.store') }}" method="POST" class="row g-3">
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
                        <label class="form-label">Deduction Type <span class="text-danger">*</span></label>
                        <select name="deduction_type_id" class="form-select @error('deduction_type_id') is-invalid @enderror" required>
                            <option value="">-- Select Type --</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" @selected(old('deduction_type_id')==$type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('deduction_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Link to Advance (Optional)</label>
                        <select name="staff_advance_id" class="form-select @error('staff_advance_id') is-invalid @enderror">
                            <option value="">-- None --</option>
                            @foreach($advances as $advance)
                                <option value="{{ $advance->id }}" @selected(old('staff_advance_id')==$advance->id)>
                                    {{ $advance->staff->name }} - Ksh {{ number_format($advance->balance, 2) }} remaining
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Link this deduction to a staff advance</div>
                        @error('staff_advance_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Deduction Amount (Ksh) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', date('Y-m-d')) }}" required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                        <div class="form-text">Leave empty for ongoing deduction</div>
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            <option value="one_time" @selected(old('frequency')==='one_time')>One Time</option>
                            <option value="monthly" @selected(old('frequency')==='monthly')>Monthly</option>
                            <option value="quarterly" @selected(old('frequency')==='quarterly')>Quarterly</option>
                            <option value="yearly" @selected(old('frequency')==='yearly')>Yearly</option>
                        </select>
                        @error('frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Amount (Optional)</label>
                        <input type="number" name="total_amount" step="0.01" min="0.01" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" id="total_amount">
                        <div class="form-text">Total amount to be deducted over installments</div>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 d-none" id="total_installments_field">
                        <label class="form-label">Total Installments</label>
                        <input type="number" name="total_installments" min="1" class="form-control @error('total_installments') is-invalid @enderror" value="{{ old('total_installments') }}">
                        <div class="form-text">Number of installments to complete</div>
                        @error('total_installments')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror" placeholder="Short description">{{ old('description') }}</textarea>
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
                        <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Deduction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('total_amount').addEventListener('input', function() {
  const totalAmount = this.value;
  const installmentsField = document.getElementById('total_installments_field');
  
  installmentsField.classList.toggle('d-none', !totalAmount);
});
</script>
@endsection

