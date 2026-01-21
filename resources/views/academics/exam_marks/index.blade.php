@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Exam Marks</h1>
        <p class="text-muted mb-0">Browse marks by exam and drill into bulk entry.</p>
      </div>
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-settings-primary"><i class="bi bi-pencil-square me-1"></i> Bulk Entry</a>
    </div>

    @includeIf('partials.alerts')

    <form method="get" class="settings-card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-5">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
              <option value="">All Exams</option>
              @foreach($exams as $e)
                <option value="{{ $e->id }}" @selected(request('exam_id')==$e->id)>{{ $e->name }} ({{ $e->term?->name }}/{{ $e->academicYear?->year }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-ghost-strong w-100"><i class="bi bi-funnel"></i></button>
          </div>
        </div>
      </div>
    </form>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Exam</th>
                <th class="text-center">Score</th>
                <th class="text-center">Grade</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($marks as $m)
                <tr>
                  <td>{{ $m->id }}</td>
                  <td>{{ $m->student?->full_name }}</td>
                  <td>{{ $m->subject?->name ?? '—' }}</td>
                  <td>{{ $m->exam?->name ?? '—' }}</td>
                  <td class="text-center">{{ is_null($m->score_raw) ? '—' : number_format($m->score_raw,2) }}</td>
                  <td class="text-center">{{ $m->grade_label ?? '—' }}</td>
                  <td><span class="pill-badge pill-muted">{{ ucfirst($m->status ?? 'draft') }}</span></td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No marks found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">{{ $marks->withQueryString()->links() }}</div>
    </div>
  </div>
</div>
@endsection
