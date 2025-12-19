@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Schemes of Work</div>
        <h1 class="mb-1">Schemes of Work</h1>
        <p class="text-muted mb-0">Plan coverage, monitor progress, and export.</p>
      </div>
      @can('schemes_of_work.create')
      <a href="{{ route('academics.schemes-of-work.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Create Scheme</a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classrooms</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">All Subjects</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
              <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Classroom</th>
                <th>Year</th>
                <th>Term</th>
                <th>Progress</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schemes as $scheme)
                <tr>
                  <td class="fw-semibold">{{ $scheme->title }}</td>
                  <td>{{ $scheme->subject->name }}</td>
                  <td>{{ $scheme->classroom->name }}</td>
                  <td>{{ $scheme->academicYear->year }}</td>
                  <td>{{ $scheme->term->name }}</td>
                  <td>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar" role="progressbar" style="width: {{ $scheme->progress_percentage }}%"></div>
                    </div>
                    <small class="text-muted">{{ $scheme->progress_percentage }}%</small>
                  </td>
                  <td><span class="pill-badge pill-{{ $scheme->status == 'active' ? 'success' : ($scheme->status == 'completed' ? 'info' : 'warning') }}">{{ ucfirst($scheme->status) }}</span></td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.schemes-of-work.show', $scheme) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @can('schemes_of_work.edit')
                      <a href="{{ route('academics.schemes-of-work.edit', $scheme) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @endcan
                      @can('schemes_of_work.export_pdf')
                      <a href="{{ route('academics.schemes-of-work.export-pdf', $scheme) }}" class="btn btn-sm btn-ghost-strong text-danger" title="Export PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                      @endcan
                      @can('schemes_of_work.export_excel')
                      <a href="{{ route('academics.schemes-of-work.export-excel', $scheme) }}" class="btn btn-sm btn-ghost-strong text-success" title="Export Excel"><i class="bi bi-file-excel"></i></a>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No schemes of work found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $schemes->links() }}</div>
    </div>
  </div>
</div>
@endsection
