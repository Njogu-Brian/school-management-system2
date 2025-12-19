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
        <h1 class="mb-1">Exam Results</h1>
        <p class="text-muted mb-0">Review and publish results to report cards.</p>
      </div>
      <form method="get" class="d-flex gap-2">
        <select name="exam_id" class="form-select" onchange="this.form.submit()">
          <option value="">All Exams</option>
          @foreach($exams as $e)
            <option value="{{ $e->id }}" @selected($examId==$e->id)>{{ $e->name }} ({{ $e->term?->name }} {{ $e->academicYear?->year }})</option>
          @endforeach
        </select>
        <button class="btn btn-ghost-strong"><i class="bi bi-search"></i></button>
      </form>
    </div>

    @includeIf('partials.alerts')

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
                <th class="text-center">Mark</th>
                <th class="text-center">Grade</th>
                <th class="text-center">Points</th>
                <th>Remark</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($marks as $m)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $m->student?->full_name ?? '-' }}</td>
                  <td>{{ $m->subject?->name ?? '-' }}</td>
                  <td>{{ $m->exam?->name ?? '-' }}</td>
                  <td class="text-center">{{ $m->score_raw ?? '-' }}</td>
                  <td class="text-center"><span class="pill-badge pill-success">{{ $m->grade_label ?? '-' }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-info">{{ $m->pl_level ?? '-' }}</span></td>
                  <td>{{ $m->subject_remark ?? '-' }}</td>
                  <td>
                    @if($m->status=='submitted')
                      <span class="pill-badge pill-muted">Submitted</span>
                    @elseif($m->status=='approved')
                      <span class="pill-badge pill-success">Approved</span>
                    @else
                      <span class="pill-badge pill-muted">{{ ucfirst($m->status) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No results available.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if(method_exists($marks,'links'))
        <div class="card-footer">{{ $marks->withQueryString()->links() }}</div>
      @endif
    </div>

    @if($examId)
      <div class="mt-3 text-end">
        <form action="{{ route('exams.publish', $examId) }}" method="post" onsubmit="return confirm('Publish results for this exam to report cards?');">
          @csrf
          <button class="btn btn-settings-primary"><i class="bi bi-cloud-upload"></i> Publish Results</button>
        </form>
      </div>
    @endif
  </div>
</div>
@endsection
