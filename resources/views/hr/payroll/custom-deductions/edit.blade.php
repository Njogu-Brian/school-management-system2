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
                <h1 class="mb-1">Edit Custom Deduction</h1>
                <p class="text-muted mb-0">Update deduction details.</p>
            </div>
            <a href="{{ route('hr.payroll.custom-deductions.show', $deduction->id) }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Details
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Deduction Details</h5>
                    <p class="text-muted small mb-0">Adjust amount, dates, and frequency.</p>
                </div>
                <span class="pill-badge pill-secondary">Staff & type locked</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.custom-deductions.update', $deduction->id) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label">Staff</label>
                        <input type="text" class="form-control" value="{{ $deduction->staff->name }}" disabled>
                        <div class="form-text">Staff cannot be changed</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Deduction Type</label>
                        <input type="text" class="form-control" value="{{ $deduction->deductionType->name }}" disabled>
                        <div class="form-text">Deduction type cannot be changed</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Deduction Amount (Ksh) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $deduction->amount) }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            <option value="one_time" @selected(old('frequency', $deduction->frequency)==='one_time')>One Time</option>
                            <option value="monthly" @selected(old('frequency', $deduction->frequency)==='monthly')>Monthly</option>
                            <option value="quarterly" @selected(old('frequency', $deduction->frequency)==='quarterly')>Quarterly</option>
                            <option value="yearly" @selected(old('frequency', $deduction->frequency)==='yearly')>Yearly</option>
                        </select>
                        @error('frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $deduction->effective_from->format('Y-m-d')) }}" required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $deduction->effective_to?->format('Y-m-d')) }}">
                        <div class="form-text">Leave empty for ongoing deduction</div>
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror" placeholder="Short description">{{ old('description', $deduction->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes">{{ old('notes', $deduction->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('hr.payroll.custom-deductions.show', $deduction->id) }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Update Deduction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

