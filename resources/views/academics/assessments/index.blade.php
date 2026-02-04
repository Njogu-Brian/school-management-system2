@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Assessments</h1>
        <p class="text-muted mb-0">Recent assessment entries by class and subject.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
          <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
          <button type="submit" class="btn btn-ghost-strong">Filter</button>
        </form>
        <a href="{{ route('academics.assessments.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> New Assessment
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Assessment List</h5>
          <p class="text-muted small mb-0">Date, class, subject, score and teacher.</p>
        </div>
        <span class="input-chip">{{ $assessments->count() }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Week Ending</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Student</th>
                <th>Score</th>
                <th>%</th>
                <th>Teacher</th>
              </tr>
            </thead>
            <tbody>
              @forelse($assessments as $assessment)
                <tr>
                  <td>{{ optional($assessment->assessment_date)->format('Y-m-d') }}</td>
                  <td>{{ optional($assessment->week_ending)->format('Y-m-d') }}</td>
                  <td>{{ $assessment->classroom?->name }}</td>
                  <td>{{ $assessment->subject?->name }}</td>
                  <td>{{ $assessment->student?->full_name ?? $assessment->student?->name }}</td>
                  <td>{{ $assessment->score }} / {{ $assessment->out_of }}</td>
                  <td>{{ $assessment->score_percent }}</td>
                  <td>{{ $assessment->staff?->full_name ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted p-4">No assessments found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
