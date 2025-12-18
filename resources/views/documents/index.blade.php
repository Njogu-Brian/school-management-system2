@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Documents / Management</div>
                <h1>Document Management</h1>
                <p>Organize, filter, and download key documents.</p>
            </div>
            <a href="{{ route('documents.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-circle"></i> Upload Document
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') == $cat)>{{ ucwords(str_replace('_',' ', $cat)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" class="form-select">
                            <option value="">All</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('document_type') == $type)>{{ ucwords(str_replace('_',' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Title / Attached To</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search title or entity">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('documents.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents</h5>
                <span class="input-chip">{{ $documents->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
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
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td class="fw-semibold">{{ $document->title }}</td>
                                    <td><span class="pill-badge">{{ ucfirst($document->category) }}</span></td>
                                    <td><span class="input-chip">{{ ucfirst($document->document_type) }}</span></td>
                                    <td>
                                        @if($document->documentable)
                                            {{ class_basename($document->documentable_type) }}:
                                            {{ $document->documentable->name ?? $document->documentable->first_name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $document->file_size_human }}</td>
                                    <td>{{ $document->version }}</td>
                                    <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-ghost-strong">View</a>
                                        <a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-sm btn-ghost-strong">Preview</a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-settings-primary">Download</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No documents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $documents->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

