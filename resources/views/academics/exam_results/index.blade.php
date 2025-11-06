@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Exam Results</h3>
    <form method="get" class="d-flex gap-2">
      <select name="exam_id" class="form-select" onchange="this.form.submit()">
        <option value="">All Exams</option>
        @foreach($exams as $e)
          <option value="{{ $e->id }}" @selected($examId==$e->id)>
            {{ $e->name }} ({{ $e->term?->name }} {{ $e->academicYear?->year }})
          </option>
        @endforeach
      </select>
      <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  @includeIf('partials.alerts')

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
                <td class="text-center"><span class="badge bg-success">{{ $m->grade_label ?? '-' }}</span></td>
                <td class="text-center"><span class="badge bg-info text-dark">{{ $m->pl_level ?? '-' }}</span></td>
                <td>{{ $m->subject_remark ?? '-' }}</td>
                <td>
                  @if($m->status=='submitted')
                    <span class="badge bg-secondary">Submitted</span>
                  @elseif($m->status=='approved')
                    <span class="badge bg-success">Approved</span>
                  @else
                    <span class="badge bg-light text-dark">{{ ucfirst($m->status) }}</span>
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
    <div class="mt-4 text-end">
      <form action="{{ route('exams.publish', $examId) }}" method="post"
            onsubmit="return confirm('Publish results for this exam to report cards?');">
        @csrf
        <button class="btn btn-success">
          <i class="bi bi-cloud-upload"></i> Publish Results
        </button>
      </form>
    </div>
  @endif
</div>
@endsection
