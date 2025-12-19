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
        <h1 class="mb-1">Results Published</h1>
        <p class="text-muted mb-0">{{ $exam->name }} results pushed to report cards.</p>
      </div>
      <a href="{{ route('academics.exams.results.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back to Results</a>
    </div>

    <div class="alert alert-success alert-soft border-0">
      <i class="bi bi-check-circle-fill me-2"></i> Results for <strong>{{ $exam->name }}</strong> have been published successfully to report cards.
    </div>

    <div class="settings-card">
      <div class="card-header fw-semibold">Updated Report Cards</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
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
                  <td><span class="pill-badge pill-primary">{{ $rc->overall_grade ?? '-' }}</span></td>
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
</div>
@endsection
