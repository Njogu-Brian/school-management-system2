@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Add Leave Type</h1>
                <p class="text-muted mb-0">Create a new leave category with rules.</p>
            </div>
            <a href="{{ route('staff.leave-types.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Leave Type Information</h5>
                    <p class="text-muted small mb-0">Name, code, limits, and policy flags.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.leave-types.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., Annual Leave">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required placeholder="e.g., ANNUAL" style="text-transform:uppercase">
                        <small class="text-muted">Unique code for this leave type</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Maximum Days</label>
                        <input type="number" name="max_days" class="form-control" value="{{ old('max_days') }}" min="0" placeholder="Leave empty for unlimited">
                        <small class="text-muted">Maximum days per year (blank = unlimited)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this leave type">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_paid" value="1" class="form-check-input" id="is_paid" @checked(old('is_paid', true))>
                            <label class="form-check-label" for="is_paid">
                                Paid Leave
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="requires_approval" @checked(old('requires_approval', true))>
                            <label class="form-check-label" for="requires_approval">
                                Requires Approval
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('staff.leave-types.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Leave Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

