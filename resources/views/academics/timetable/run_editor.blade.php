@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable · Draft run</div>
        <h1 class="mb-1">Run #{{ $run->id }} ({{ ucfirst($run->status) }})</h1>
        <p class="text-muted mb-0">Edit slots, lock important periods, or regenerate a single stream.</p>
      </div>
      <a href="{{ route('academics.timetable.whole-school') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Whole-school</a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select" onchange="this.form.submit()">
              <option value="">Select stream</option>
              @foreach($streams as $s)
                <option value="{{ $s->id }}" {{ $stream && $stream->id === $s->id ? 'selected' : '' }}>
                  {{ $s->name }}{{ $s->classroom?->name ? ' · '.$s->classroom->name : '' }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            @if($stream)
            <form method="POST" action="{{ route('academics.timetable.whole-school.run.regenerate-stream', $run) }}">
              @csrf
              <input type="hidden" name="stream_id" value="{{ $stream->id }}">
              <button type="submit" class="btn btn-warning w-100"><i class="bi bi-arrow-repeat"></i> Regenerate this stream</button>
            </form>
            @endif
          </div>
        </form>
      </div>
    </div>

    @if($stream && $periods->count())
      @php
        $days = $periods->pluck('day')->unique()->values();
        $byDay = $periods->groupBy('day');
      @endphp

      @foreach($days as $day)
        <div class="settings-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $day }}</strong>
            <span class="text-muted small">Stream: {{ $stream->name }}</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 160px;">Time</th>
                    <th>Slot</th>
                    <th style="width: 90px;">Lock</th>
                    <th style="width: 220px;">Update</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(($byDay[$day] ?? collect()) as $p)
                    @php
                      $slot = $slots[$p->id] ?? null;
                      $locked = $locks[$p->id] ?? null;
                    @endphp
                    <tr>
                      <td class="fw-semibold">{{ $p->start_time }} - {{ $p->end_time }}</td>
                      <td>
                        @if($p->slot_type !== 'lesson')
                          <span class="pill-badge pill-info">{{ strtoupper($p->slot_type) }}</span>
                          <span class="ms-2 text-muted">{{ $slot?->label ?? $p->label ?? '-' }}</span>
                        @else
                          <div class="fw-semibold">
                            Subject: <span class="text-muted">{{ $slot?->subject_id ?? '-' }}</span>
                            @if($locked) <span class="pill-badge pill-warning ms-2">LOCKED</span> @endif
                          </div>
                          <div class="small text-muted">Teacher: {{ $slot?->staff_id ?? '-' }}</div>
                        @endif
                      </td>
                      <td>
                        @if($p->slot_type === 'lesson')
                        <form method="POST" action="{{ route('academics.timetable.whole-school.run.lock', $run) }}">
                          @csrf
                          <input type="hidden" name="stream_id" value="{{ $stream->id }}">
                          <input type="hidden" name="layout_period_id" value="{{ $p->id }}">
                          <button type="submit" class="btn btn-sm {{ $locked ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            {{ $locked ? 'Unlock' : 'Lock' }}
                          </button>
                        </form>
                        @endif
                      </td>
                      <td>
                        @if($p->slot_type === 'lesson')
                          <form method="POST" action="{{ route('academics.timetable.whole-school.run.slot', $run) }}" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="stream_id" value="{{ $stream->id }}">
                            <input type="hidden" name="layout_period_id" value="{{ $p->id }}">
                            <select class="form-select form-select-sm" name="subject_id" style="max-width: 160px;">
                              <option value="">Subject</option>
                              @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" {{ (int)($slot?->subject_id ?? 0) === (int)$sub->id ? 'selected' : '' }}>
                                  {{ $sub->name }}
                                </option>
                              @endforeach
                            </select>
                            <select class="form-select form-select-sm" name="staff_id" style="max-width: 160px;">
                              <option value="">Teacher</option>
                              @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ (int)($slot?->staff_id ?? 0) === (int)$t->id ? 'selected' : '' }}>
                                  {{ $t->full_name }}
                                </option>
                              @endforeach
                            </select>
                            <button class="btn btn-sm btn-settings-primary" type="submit">Save</button>
                          </form>
                        @else
                          <span class="text-muted small">Not editable.</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endforeach
    @else
      <div class="alert alert-info">Select a stream to edit this run.</div>
    @endif
  </div>
</div>
@endsection

