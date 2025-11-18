@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Curriculum Designs</h1>
        <div>
            @can('curriculum_designs.create')
            <a href="{{ route('academics.curriculum-designs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Upload Curriculum Design
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select">
                        <option value="">All Subjects</option>
                        @if(isset($subjects))
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class Level</label>
                    <input type="text" name="class_level" class="form-control" value="{{ request('class_level') }}" placeholder="e.g., Grade 4">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by title...">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Curriculum Designs List -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($curriculumDesigns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Class Level</th>
                                <th>Pages</th>
                                <th>Status</th>
                                <th>Uploaded By</th>
                                <th>Uploaded At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($curriculumDesigns as $design)
                                <tr>
                                    <td>
                                        <strong>{{ $design->title }}</strong>
                                    </td>
                                    <td>{{ $design->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $design->class_level ?? 'N/A' }}</td>
                                    <td>{{ $design->pages }}</td>
                                    <td>
                                        @if($design->status === 'processed')
                                            <span class="badge bg-success">Processed</span>
                                        @elseif($design->status === 'processing')
                                            <span class="badge bg-warning">Processing</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>{{ $design->uploader->name ?? 'N/A' }}</td>
                                    <td>{{ $design->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('academics.curriculum-designs.show', $design) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($design->status === 'processed')
                                                <a href="{{ route('academics.curriculum-designs.review', $design) }}" class="btn btn-sm btn-primary" title="Review">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                            @endif
                                            @can('curriculum_designs.edit', $design)
                                                <a href="{{ route('academics.curriculum-designs.edit', $design) }}" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan
                                            @can('curriculum_designs.delete', $design)
                                                <form action="{{ route('academics.curriculum-designs.destroy', $design) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this curriculum design?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $curriculumDesigns->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-pdf" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No curriculum designs found.</p>
                    @can('curriculum_designs.create')
                        <a href="{{ route('academics.curriculum-designs.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Upload Your First Curriculum Design
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

