@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Portfolio Assessments</div>
        <h1 class="mb-1">Portfolio Assessments</h1>
        <p class="text-muted mb-0">Track student portfolios, scores, and feedback.</p>
      </div>
      <a href="{{ route('academics.portfolio-assessments.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Create Assessment</a>
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
          <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="portfolio_type" class="form-select">
              <option value="">All Types</option>
              <option value="project" {{ request('portfolio_type') == 'project' ? 'selected' : '' }}>Project</option>
              <option value="practical" {{ request('portfolio_type') == 'practical' ? 'selected' : '' }}>Practical</option>
              <option value="creative" {{ request('portfolio_type') == 'creative' ? 'selected' : '' }}>Creative</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="assessed" {{ request('status') == 'assessed' ? 'selected' : '' }}>Assessed</option>
            </select>
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
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
                <th>Student</th>
                <th>Subject</th>
                <th>Type</th>
                <th>Score</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($portfolios as $portfolio)
              <tr>
                <td class="fw-semibold">{{ $portfolio->title }}</td>
                <td>{{ $portfolio->student->full_name }}</td>
                <td>{{ $portfolio->subject->name }}</td>
                <td><span class="pill-badge pill-info">{{ ucfirst($portfolio->portfolio_type) }}</span></td>
                <td>{{ $portfolio->total_score ?? 'N/A' }}</td>
                <td><span class="pill-badge pill-{{ $portfolio->status == 'assessed' ? 'success' : ($portfolio->status == 'published' ? 'primary' : 'warning') }}">{{ ucfirst($portfolio->status) }}</span></td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    <a href="{{ route('academics.portfolio-assessments.show', $portfolio) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="{{ route('academics.portfolio-assessments.edit', $portfolio) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="7" class="text-center text-muted py-4">No portfolio assessments found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $portfolios->links() }}</div>
    </div>
  </div>
</div>
@endsection
