@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Results Published</h3>
    <a href="{{ route('academics.exams.results.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back to Results
    </a>
  </div>

  <div class="alert alert-success shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i>
    Results for <strong>{{ $exam->name }}</strong> have been published successfully to report cards.
  </div>

  <div class="card shadow-sm">
    <div class="card-header fw-semibold">
      Updated Report Cards
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Class</th>
              <th>Subjects Updated</th>
              <th>Average (%)</th>
              <th>Grade</th>
              <th>Generated At</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reportCards as $rc)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $rc->student?->full_name ?? '-' }}</td>
                <td>{{ $rc->classroom?->name ?? '-' }}</td>
                <td>{{ $rc->items_count ?? 0 }}</td>
                <td>{{ number_format($rc->average_score, 2) }}</td>
                <td><span class="badge bg-primary">{{ $rc->overall_grade ?? '-' }}</span></td>
                <td>{{ $rc->updated_at->format('d M Y, H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">No report cards updated.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
