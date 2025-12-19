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
                <h1 class="mb-1">Edit Salary Structure</h1>
                <p class="text-muted mb-0">Update salary structure details.</p>
            </div>
            <a href="{{ route('hr.payroll.salary-structures.show', $structure->id) }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Details
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Structure Details</h5>
                    <p class="text-muted small mb-0">Adjust base pay, allowances, and dates.</p>
                </div>
                <span class="pill-badge pill-secondary">Ref #{{ $structure->id }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.salary-structures.update', $structure->id) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label">Staff</label>
                        <input type="text" class="form-control" value="{{ $structure->staff->name }}" disabled>
                        <div class="form-text">Staff cannot be changed</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Basic Salary (Ksh) <span class="text-danger">*</span></label>
                        <input type="number" name="basic_salary" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $structure->basic_salary) }}" required>
                        @error('basic_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Housing Allowance (Ksh)</label>
                        <input type="number" name="housing_allowance" step="0.01" min="0" class="form-control @error('housing_allowance') is-invalid @enderror" value="{{ old('housing_allowance', $structure->housing_allowance) }}">
                        @error('housing_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Transport Allowance (Ksh)</label>
                        <input type="number" name="transport_allowance" step="0.01" min="0" class="form-control @error('transport_allowance') is-invalid @enderror" value="{{ old('transport_allowance', $structure->transport_allowance) }}">
                        @error('transport_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Medical Allowance (Ksh)</label>
                        <input type="number" name="medical_allowance" step="0.01" min="0" class="form-control @error('medical_allowance') is-invalid @enderror" value="{{ old('medical_allowance', $structure->medical_allowance) }}">
                        @error('medical_allowance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Other Allowances (Ksh)</label>
                        <input type="number" name="other_allowances" step="0.01" min="0" class="form-control @error('other_allowances') is-invalid @enderror" value="{{ old('other_allowances', $structure->other_allowances) }}">
                        @error('other_allowances')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $structure->effective_from->format('Y-m-d')) }}" required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $structure->effective_to?->format('Y-m-d')) }}">
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $structure->is_active))>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes">{{ old('notes', $structure->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('hr.payroll.salary-structures.show', $structure->id) }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Update Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection