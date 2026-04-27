@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable</div>
        <h1 class="mb-1">Substitutions</h1>
        <p class="text-muted mb-0">Create a date-specific override without regenerating the weekly timetable.</p>
      </div>
      <a href="{{ route('academics.timetable.whole-school') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Whole-school</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="POST" action="{{ route('academics.timetable.whole-school.substitutions.store') }}" class="row g-3 align-items-end">
          @csrf
          <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="effective_date" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream</label>
            <select class="form-select" name="stream_id" required>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->classroom?->name ? ' · '.$s->classroom->name : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Layout period</label>
            <select class="form-select" name="layout_period_id" required>
              @foreach($periods as $p)
                <option value="{{ $p->id }}">{{ $p->day }} · {{ $p->start_time }}-{{ $p->end_time }} · {{ $p->slot_type }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Subject</label>
            <select class="form-select" name="subject_id">
              <option value="">(keep)</option>
              @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Teacher</label>
            <select class="form-select" name="staff_id">
              <option value="">(none)</option>
              @foreach($teachers as $t)
                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Reason</label>
            <input class="form-control" name="reason" placeholder="e.g. Sick leave, training, meeting">
          </div>
          <div class="col-md-3">
            <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-save"></i> Save substitution</button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header"><strong>Recent substitutions</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Stream</th>
                <th>Day</th>
                <th>Period</th>
                <th>Subject ID</th>
                <th>Teacher ID</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              @forelse($overrides as $o)
                <tr>
                  <td class="fw-semibold">{{ optional($o->effective_date)->toDateString() }}</td>
                  <td>{{ $o->stream_id }}</td>
                  <td>{{ $o->day }}</td>
                  <td>{{ $o->layout_period_id }}</td>
                  <td>{{ $o->subject_id ?? '-' }}</td>
                  <td>{{ $o->staff_id ?? '-' }}</td>
                  <td class="text-muted">{{ $o->reason }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No substitutions yet</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

