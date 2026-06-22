@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics / Teacher Assignments</div>
        <h1 class="mb-1">Teaching Assignments — {{ $staff->full_name }}</h1>
        <p class="text-muted mb-0">
          Assign streams, learning areas (subjects), class teacher, and assistant teacher roles at once.
        </p>
      </div>
      <a href="{{ route('academics.teacher-assignments.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> All Teachers
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Teacher Streams</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('academics.teacher-assignments.update', $staff->id) }}" method="POST">
          @csrf
          @include('academics.teacher_assignments.partials.streams_form', [
              'staff' => $staff,
              'streamSlots' => $streamSlots,
              'subjectsBySlot' => $subjectsBySlot,
              'selectedSlotKeys' => $selectedSlotKeys,
              'slotData' => $slotData,
          ])
          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.teacher-assignments.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-check-circle"></i> Save Assignments
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card mt-3">
      <div class="card-body small text-muted">
        <p class="mb-1"><strong>Class Teacher</strong> — marks attendance, views transport and bio-data, views all academic data (read-only except subjects they teach), views homework, controls student diaries.</p>
        <p class="mb-0"><strong>Subject Teacher</strong> — issues homework, speed tests, enters marks and results, comments on report cards for assigned subjects only.</p>
      </div>
    </div>
  </div>
</div>
@endsection

@stack('scripts')
