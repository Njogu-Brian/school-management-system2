@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Exam Marks</h3>
      <small class="text-muted">Browse marks by exam, then drill into bulk entry.</small>
    </div>
    <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-primary">
      <i class="bi bi-pencil-square me-1"></i> Bulk Entry
    </a>
  </div>

  @includeIf('partials.alerts')

  <form method="get" class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
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
          <button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button>
        </div>
      </div>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                <td>{{ $m->student?->full_name ?? ($m->student?->first_name.' '.$m->student?->last_name) }}</td>
                <td>{{ $m->subject?->name ?? '—' }}</td>
                <td>{{ $m->exam?->name ?? '—' }}</td>
                <td class="text-center">{{ is_null($m->score_raw) ? '—' : number_format($m->score_raw,2) }}</td>
                <td class="text-center">{{ $m->grade_label ?? '—' }}</td>
                <td><span class="badge text-bg-light">{{ ucfirst($m->status ?? 'draft') }}</span></td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">No marks found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">
      {{ $marks->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
