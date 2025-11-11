@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Staff Documents</h2>
            <small class="text-muted">Manage staff documents and certificates</small>
        </div>
        <a href="{{ route('staff.documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Upload Document
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
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
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Documents</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                    <span class="badge bg-info">{{ $documentTypes[$document->document_type] ?? $document->document_type }}</span>
                                </td>
                                <td>
                                    @if($document->expiry_date)
                                        @if($document->isExpired())
                                            <span class="badge bg-danger">{{ $document->expiry_date->format('d M Y') }} (Expired)</span>
                                        @elseif($document->isExpiringSoon())
                                            <span class="badge bg-warning">{{ $document->expiry_date->format('d M Y') }} (Expiring Soon)</span>
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
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('staff.documents.show', $document->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('staff.documents.download', $document->id) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteDocument({{ $document->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $document->id }}" action="{{ route('staff.documents.destroy', $document->id) }}" method="POST" style="display:none;">
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
            <div class="card-footer">
                {{ $documents->withQueryString()->links() }}
            </div>
        @endif
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

