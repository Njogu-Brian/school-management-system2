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
                <h1 class="mb-1">Upload Document</h1>
                <p class="text-muted mb-0">Upload a document for a staff member.</p>
            </div>
            <a href="{{ route('staff.documents.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-upload"></i> Document Information</h5>
                    <p class="text-muted small mb-0">Staff selection, type, and file upload.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.documents.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('staff_id', $staffId) == $s->id)>{{ $s->full_name }} ({{ $s->staff_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            @foreach($documentTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('document_type') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="e.g., Employment Contract 2024">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max: 10MB)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                        <small class="text-muted">Leave empty if document doesn't expire</small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional description...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('staff.documents.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-upload"></i> Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

