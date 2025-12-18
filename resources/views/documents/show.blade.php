@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Documents / Detail</div>
                <h1>{{ $document->title }}</h1>
                <p>View details, download, and upload a new version.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-ghost-strong"><i class="bi bi-eye"></i> Preview</a>
                <a href="{{ route('documents.download', $document) }}" class="btn btn-settings-primary"><i class="bi bi-download"></i> Download</a>
                <button class="btn btn-ghost-strong" data-bs-toggle="collapse" data-bs-target="#emailForm"><i class="bi bi-envelope"></i> Email</button>
                <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this document?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost-strong text-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
                <a href="{{ route('documents.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Document Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <tr>
                                    <th width="200">Title</th>
                                    <td class="fw-semibold">{{ $document->title }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $document->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td><span class="pill-badge">{{ ucwords(str_replace('_',' ', $document->category)) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Document Type</th>
                                    <td><span class="input-chip">{{ ucwords(str_replace('_',' ', $document->document_type)) }}</span></td>
                                </tr>
                                <tr>
                                    <th>File Name</th>
                                    <td>{{ $document->file_name }}</td>
                                </tr>
                                <tr>
                                    <th>File Size</th>
                                    <td>{{ $document->file_size_human }}</td>
                                </tr>
                                <tr>
                                    <th>Version</th>
                                    <td>{{ $document->version }}</td>
                                </tr>
                                @if($document->documentable)
                                <tr>
                                    <th>Attached To</th>
                                    <td>
                                        {{ class_basename($document->documentable_type) }}:
                                        {{ $document->documentable->name ?? $document->documentable->first_name ?? 'N/A' }}
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Uploaded By</th>
                                    <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Uploaded At</th>
                                    <td>{{ $document->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card mb-3 collapse" id="emailForm">
                    <div class="card-header">
                        <h5 class="mb-0">Email this document</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('documents.email', $document) }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">To</label>
                                <input type="email" name="to" class="form-control" placeholder="recipient@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="Document: {{ $document->title }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Optional note"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-send"></i> Send</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload New Version</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('documents.version', $document) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">New File</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-settings-primary w-100">Upload New Version</button>
                            </div>
                        </form>
                    </div>
                </div>

                @if($document->versions->count() > 0)
                <div class="settings-card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Version History</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($document->versions as $version)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Version {{ $version->version }}</span>
                                    <a href="{{ route('documents.download', $version) }}" class="btn btn-sm btn-ghost-strong">Download</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

