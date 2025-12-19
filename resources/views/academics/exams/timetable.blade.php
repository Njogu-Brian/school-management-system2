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
        <h1 class="mb-1">Exam Timetable</h1>
        <p class="text-muted mb-0">Daily schedule of exam papers.</p>
      </div>
    </div>

    @forelse($schedules as $day => $list)
      <div class="settings-card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-calendar-week"></i>
          <h5 class="mb-0">{{ \Carbon\Carbon::parse($day)->format('l, d M Y') }}</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 15%;">Time</th>
                  <th style="width: 25%;">Exam</th>
                  <th style="width: 20%;">Subject</th>
                  <th style="width: 20%;">Classroom</th>
                  <th style="width: 20%;">Term / Year</th>
                </tr>
              </thead>
              <tbody>
                @foreach($list as $schedule)
                  <tr>
                    <td><span class="pill-badge pill-info">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} – {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '' }}</span></td>
                    <td><strong>{{ $schedule->exam->name }}</strong> <span class="pill-badge pill-secondary ms-1">{{ strtoupper($schedule->exam->type) }}</span></td>
                    <td>{{ $schedule->subject->name ?? '-' }}</td>
                    <td><i class="bi bi-house"></i> {{ $schedule->classroom->name ?? '-' }}</td>
                    <td>{{ $schedule->exam->term->name ?? '-' }} / {{ $schedule->exam->academicYear->year ?? '-' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @empty
      <div class="alert alert-info alert-soft border-0">No scheduled exam papers.</div>
    @endforelse
  </div>
</div>
@endsection
