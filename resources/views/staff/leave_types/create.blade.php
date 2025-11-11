@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Add Leave Type</h2>
            <small class="text-muted">Create a new leave type</small>
        </div>
        <a href="{{ route('staff.leave-types.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Leave Type Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('staff.leave-types.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g., Annual Leave">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required placeholder="e.g., ANNUAL" style="text-transform:uppercase">
                        <small class="text-muted">Unique code for this leave type</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Maximum Days</label>
                        <input type="number" name="max_days" class="form-control" value="{{ old('max_days') }}" min="0" placeholder="Leave empty for unlimited">
                        <small class="text-muted">Maximum days allowed per year (leave empty for unlimited)</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this leave type">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_paid" value="1" class="form-check-input" id="is_paid" @checked(old('is_paid', true))>
                            <label class="form-check-label" for="is_paid">
                                Paid Leave
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="requires_approval" @checked(old('requires_approval', true))>
                            <label class="form-check-label" for="requires_approval">
                                Requires Approval
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('staff.leave-types.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Leave Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

