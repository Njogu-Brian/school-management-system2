@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Document Management</h1>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Upload Document
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All</option>
                        <option value="student" {{ request('category') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="staff" {{ request('category') == 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="academic" {{ request('category') == 'academic' ? 'selected' : '' }}>Academic</option>
                        <option value="financial" {{ request('category') == 'financial' ? 'selected' : '' }}>Financial</option>
                        <option value="administrative" {{ request('category') == 'administrative' ? 'selected' : '' }}>Administrative</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Document Type</label>
                    <select name="document_type" class="form-select">
                        <option value="">All</option>
                        <option value="report" {{ request('document_type') == 'report' ? 'selected' : '' }}>Report</option>
                        <option value="certificate" {{ request('document_type') == 'certificate' ? 'selected' : '' }}>Certificate</option>
                        <option value="letter" {{ request('document_type') == 'letter' ? 'selected' : '' }}>Letter</option>
                        <option value="form" {{ request('document_type') == 'form' ? 'selected' : '' }}>Form</option>
                        <option value="policy" {{ request('document_type') == 'policy' ? 'selected' : '' }}>Policy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Attached To</th>
                            <th>File Size</th>
                            <th>Version</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr>
                                <td>{{ $document->title }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($document->category) }}</span></td>
                                <td><span class="badge bg-secondary">{{ ucfirst($document->document_type) }}</span></td>
                                <td>
                                    @if($document->documentable)
                                        {{ class_basename($document->documentable_type) }}: 
                                        {{ $document->documentable->name ?? $document->documentable->first_name ?? 'N/A' }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $document->file_size_human }}</td>
                                <td>{{ $document->version }}</td>
                                <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                                <td>{{ $document->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-primary">View</a>
                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-success">Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection

