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
        <h1 class="mb-1">Exam Schedule</h1>
        <p class="text-muted mb-0">Manage papers and times for this exam.</p>
      </div>
      <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Scheduled Papers</h5>
        @if(isset($exam))
          <span class="input-chip">{{ $exam->name }}</span>
        @endif
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Subject</th>
                <th>Classroom</th>
                <th>Invigilator</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schedules ?? [] as $schedule)
                <tr>
                  <td>{{ optional($schedule->exam_date)->format('M d, Y') ?? '—' }}</td>
                  <td>
                    @if($schedule->start_time)
                      <span class="pill-badge pill-info">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} @if($schedule->end_time) – {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} @endif</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>{{ $schedule->subject->name ?? '—' }}</td>
                  <td>{{ $schedule->classroom->name ?? '—' }}</td>
                  <td>{{ $schedule->invigilator ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No schedule entries.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
