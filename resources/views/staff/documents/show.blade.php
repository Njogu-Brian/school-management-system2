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
                <h1 class="mb-1">Document Details</h1>
                <p class="text-muted mb-0">View document information.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('staff.documents.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('staff.documents.download', $document->id) }}" class="btn btn-settings-primary">
                    <i class="bi bi-download"></i> Download
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Document Information</h5>
                            <p class="text-muted small mb-0">Staff, type, and metadata.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $document->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Staff Member</label>
                                <div class="fw-semibold">{{ $document->staff->full_name }}</div>
                                <small class="text-muted">{{ $document->staff->staff_id }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Document Type</label>
                                @php
                                    $documentTypes = [
                                        'contract' => 'Employment Contract',
                                        'certificate' => 'Certificate',
                                        'id_copy' => 'ID Copy',
                                        'qualification' => 'Qualification',
                                        'other' => 'Other',
                                    ];
                                @endphp
                                <div>
                                    <span class="pill-badge pill-info">{{ $documentTypes[$document->document_type] ?? $document->document_type }}</span>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="text-muted small">Title</label>
                                <div class="fw-semibold">{{ $document->title }}</div>
                            </div>
                            @if($document->expiry_date)
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Expiry Date</label>
                                    <div>
                                        @if($document->isExpired())
                                            <span class="pill-badge pill-danger">{{ $document->expiry_date->format('d M Y') }} (Expired)</span>
                                        @elseif($document->isExpiringSoon())
                                            <span class="pill-badge pill-warning">{{ $document->expiry_date->format('d M Y') }} (Expiring Soon)</span>
                                        @else
                                            {{ $document->expiry_date->format('d M Y') }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Uploaded</label>
                                <div>{{ $document->created_at->format('d M Y, H:i') }}</div>
                                @if($document->uploadedBy)
                                    <small class="text-muted">by {{ $document->uploadedBy->name }}</small>
                                @endif
                            </div>
                            @if($document->description)
                                <div class="col-md-12 mb-3">
                                    <label class="text-muted small">Description</label>
                                    <div>{{ $document->description }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('staff.documents.download', $document->id) }}" class="btn btn-settings-primary w-100 mb-2">
                            <i class="bi bi-download"></i> Download Document
                        </a>
                        <button type="button" class="btn btn-danger w-100" onclick="deleteDocument({{ $document->id }})">
                            <i class="bi bi-trash"></i> Delete Document
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="{{ route('staff.documents.destroy', $document->id) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endsection

