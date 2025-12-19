@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Curriculum Designs</div>
        <h1 class="mb-1">Curriculum Designs</h1>
        <p class="text-muted mb-0">Upload and review curriculum PDFs.</p>
      </div>
      @can('curriculum_designs.create')
      <a href="{{ route('academics.curriculum-designs.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Upload Curriculum Design</a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">All Subjects</option>
              @foreach($subjects ?? [] as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
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
          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        @if($curriculumDesigns->count() > 0)
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light"><tr><th>Title</th><th>Subject</th><th>Class Level</th><th>Pages</th><th>Status</th><th>Uploaded By</th><th>Uploaded At</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                @foreach($curriculumDesigns as $design)
                <tr>
                  <td class="fw-semibold">{{ $design->title }}</td>
                  <td>{{ $design->subject->name ?? 'N/A' }}</td>
                  <td>{{ $design->class_level ?? 'N/A' }}</td>
                  <td>{{ $design->pages }}</td>
                  <td><span class="pill-badge pill-{{ $design->status === 'processed' ? 'success' : ($design->status === 'processing' ? 'warning' : 'danger') }}">{{ ucfirst($design->status) }}</span></td>
                  <td>{{ $design->uploader->name ?? 'N/A' }}</td>
                  <td>{{ $design->created_at->format('M d, Y') }}</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.curriculum-designs.show', $design) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @if($design->status === 'processed')
                        <a href="{{ route('academics.curriculum-designs.review', $design) }}" class="btn btn-sm btn-ghost-strong" title="Review"><i class="bi bi-check-circle"></i></a>
                      @endif
                      @can('curriculum_designs.edit', $design)
                        <a href="{{ route('academics.curriculum-designs.edit', $design) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @endcan
                      @can('curriculum_designs.delete', $design)
                        <form action="{{ route('academics.curriculum-designs.destroy', $design) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this curriculum design?');">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      @endcan
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="card-footer d-flex justify-content-end">{{ $curriculumDesigns->links() }}</div>
        @else
          <div class="py-5 text-center">
            <i class="bi bi-file-earmark-pdf" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No curriculum designs found.</p>
            @can('curriculum_designs.create')
              <a href="{{ route('academics.curriculum-designs.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Upload Your First Curriculum Design</a>
            @endcan
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
