@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $document->title }}</h1>
        <div class="btn-group">
            <a href="{{ route('documents.download', $document) }}" class="btn btn-success">Download</a>
            <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
            <a href="{{ route('documents.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Title:</th>
                            <td>{{ $document->title }}</td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $document->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td><span class="badge bg-info">{{ ucfirst($document->category) }}</span></td>
                        </tr>
                        <tr>
                            <th>Document Type:</th>
                            <td><span class="badge bg-secondary">{{ ucfirst($document->document_type) }}</span></td>
                        </tr>
                        <tr>
                            <th>File Name:</th>
                            <td>{{ $document->file_name }}</td>
                        </tr>
                        <tr>
                            <th>File Size:</th>
                            <td>{{ $document->file_size_human }}</td>
                        </tr>
                        <tr>
                            <th>Version:</th>
                            <td>{{ $document->version }}</td>
                        </tr>
                        @if($document->documentable)
                        <tr>
                            <th>Attached To:</th>
                            <td>
                                {{ class_basename($document->documentable_type) }}: 
                                {{ $document->documentable->name ?? $document->documentable->first_name ?? 'N/A' }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Uploaded By:</th>
                            <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Uploaded At:</th>
                            <td>{{ $document->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Upload New Version</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('documents.version', $document) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">New File</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload New Version</button>
                    </form>
                </div>
            </div>

            @if($document->versions->count() > 0)
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Version History</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($document->versions as $version)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Version {{ $version->version }}</span>
                                <a href="{{ route('documents.download', $version) }}" class="btn btn-sm btn-outline-primary">Download</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

