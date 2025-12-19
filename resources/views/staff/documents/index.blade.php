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
                <h1 class="mb-1">Staff Documents</h1>
                <p class="text-muted mb-0">Manage staff documents and certificates.</p>
            </div>
            <a href="{{ route('staff.documents.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-circle"></i> Upload Document
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by staff and document type.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($documentTypes as $key => $label)
                                <option value="{{ $key }}" @selected(request('document_type') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Documents</h5>
                    <p class="mb-0 text-muted small">Expiry tracking and downloads.</p>
                </div>
                @if($documents->total() ?? null)
                    <span class="input-chip">{{ $documents->total() }} total</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Expiry Date</th>
                                <th>Uploaded</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $document->staff->full_name }}</div>
                                        <small class="text-muted">{{ $document->staff->staff_id }}</small>
                                    </td>
                                    <td>{{ $document->title }}</td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ $documentTypes[$document->document_type] ?? $document->document_type }}</span>
                                    </td>
                                    <td>
                                        @if($document->expiry_date)
                                            @if($document->isExpired())
                                                <span class="pill-badge pill-danger">{{ $document->expiry_date->format('d M Y') }} (Expired)</span>
                                            @elseif($document->isExpiringSoon())
                                                <span class="pill-badge pill-warning">{{ $document->expiry_date->format('d M Y') }} (Expiring Soon)</span>
                                            @else
                                                {{ $document->expiry_date->format('d M Y') }}
                                            @endif
                                        @else
                                            <span class="text-muted">No expiry</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $document->created_at->format('d M Y') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('staff.documents.show', $document->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.documents.download', $document->id) }}" class="btn btn-sm btn-ghost-strong" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-ghost-strong text-danger" onclick="deleteDocument({{ $document->id }})" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $document->id }}" action="{{ route('staff.documents.destroy', $document->id) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No documents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($documents->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing {{ $documents->firstItem() }}–{{ $documents->lastItem() }} of {{ $documents->total() }}
                    </div>
                    {{ $documents->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection

