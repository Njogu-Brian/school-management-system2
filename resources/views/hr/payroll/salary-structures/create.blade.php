@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Salary Structures</div>
                <h1 class="mb-1">New Salary Structure</h1>
                <p class="text-muted mb-0">Create salary structure for staff.</p>
            </div>
            <a href="{{ route('hr.payroll.salary-structures.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Structures
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Structure Details</h5>
                    <p class="text-muted small mb-0">Set base salary, allowances, and effective dates.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.salary-structures.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select @error('staff_id') is-invalid @enderror" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('staff_id', $selectedStaff?->id)==$s->id)>{{ $s->name }} ({{ $s->staff_id }})</option>
                            @endforeach
                        </select>
                        @error('staff_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Basic Salary (Ksh) <span class="text-danger">*</span></label>
                        <input type="number" name="basic_salary" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary') }}" required>
                        @error('basic_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Housing Allowance (Ksh)</label>
                        <input type="number" name="housing_allowance" step="0.01" min="0" class="form-control @error('housing_allowance') is-invalid @enderror" value="{{ old('housing_allowance') }}">
                        @error('housing_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Transport Allowance (Ksh)</label>
                        <input type="number" name="transport_allowance" step="0.01" min="0" class="form-control @error('transport_allowance') is-invalid @enderror" value="{{ old('transport_allowance') }}">
                        @error('transport_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Medical Allowance (Ksh)</label>
                        <input type="number" name="medical_allowance" step="0.01" min="0" class="form-control @error('medical_allowance') is-invalid @enderror" value="{{ old('medical_allowance') }}">
                        @error('medical_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Other Allowances (Ksh)</label>
                        <input type="number" name="other_allowances" step="0.01" min="0" class="form-control @error('other_allowances') is-invalid @enderror" value="{{ old('other_allowances') }}">
                        @error('other_allowances')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', date('Y-m-d')) }}" required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                        <div class="form-text">Leave empty for ongoing</div>
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes (optional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="alert alert-soft border-0">
                            <strong>Note:</strong> NSSF, NHIF, and PAYE deductions will be automatically calculated based on gross salary.
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('hr.payroll.salary-structures.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

