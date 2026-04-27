@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Lesson Plans</div>
        <h1 class="mb-1">Analytics</h1>
        <p class="text-muted mb-0">Submission consistency over the last {{ $days }} days ({{ $start->toDateString() }} → {{ $end->toDateString() }}).</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('academics.lesson-plans.review-queue') }}" class="btn btn-settings-primary"><i class="bi bi-inboxes"></i> Review Queue</a>
        <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Lesson Plans</a>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Lookback days</label>
            <input type="number" class="form-control" min="3" max="30" name="days" value="{{ request('days', $days) }}">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-arrow-repeat"></i> Recompute</button>
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
                <th>Teacher</th>
                <th class="text-center">Expected</th>
                <th class="text-center">Submitted</th>
                <th class="text-center">Consistency</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $r)
                <tr>
                  <td class="fw-semibold">{{ $r['teacher_name'] }}</td>
                  <td class="text-center">{{ $r['expected'] }}</td>
                  <td class="text-center">{{ $r['submitted'] }}</td>
                  <td class="text-center">
                    @php($pct = (float) $r['consistency'])
                    <span class="pill-badge pill-{{ $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger') }}">
                      {{ number_format($pct, 1) }}%
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No data available</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

