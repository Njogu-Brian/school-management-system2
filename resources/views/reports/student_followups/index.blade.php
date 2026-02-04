@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Campus & Weekly Reports</div>
        <h1 class="mb-1">Student Follow-Ups</h1>
        <p class="text-muted mb-0">Weekly student concerns and actions.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
          <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
          <button type="submit" class="btn btn-ghost-strong">Filter</button>
        </form>
        <a href="{{ route('reports.student-followups.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> New Follow-Up
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
          <h5 class="mb-0"><i class="bi bi-person-check"></i> Student Follow-Ups</h5>
          <p class="text-muted small mb-0">Week ending, student, class, academic/behavior and status.</p>
        </div>
        <span class="input-chip">{{ $reports->count() }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Week Ending</th>
                <th>Student</th>
                <th>Class</th>
                <th>Academic</th>
                <th>Behavior</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($reports as $report)
                <tr>
                  <td>{{ $report->week_ending?->format('Y-m-d') }}</td>
                  <td>{{ $report->student?->full_name ?? $report->student?->name }}</td>
                  <td>{{ $report->classroom?->name }}</td>
                  <td>{{ $report->academic_concern === null ? '—' : ($report->academic_concern ? 'Yes' : 'No') }}</td>
                  <td>{{ $report->behavior_concern === null ? '—' : ($report->behavior_concern ? 'Yes' : 'No') }}</td>
                  <td>{{ $report->progress_status ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted p-4">No follow-ups found.</td>
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
