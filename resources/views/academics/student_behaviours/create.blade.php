@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Behaviour</div>
        <h1 class="mb-1">Record Student Behaviour</h1>
        <p class="text-muted mb-0">Log behaviour for a student with term/year context.</p>
      </div>
      <a href="{{ route('academics.student-behaviours.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form action="{{ route('academics.student-behaviours.store') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select" required>
              <option value="">-- Select Student --</option>
              @foreach($students as $student)
                <option value="{{ $student->id }}">
                  {{ $student->admission_number ?? $student->admission_no }} - {{ $student->first_name }} {{ $student->middle_name ?? '' }} {{ $student->last_name }}
                  @if($student->classroom) ({{ $student->classroom->name }}) @endif
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Behaviour</label>
            <select name="behaviour_id" class="form-select" required>
              @foreach($behaviours as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
              @foreach($years as $y)
                <option value="{{ $y->id }}">{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Additional context or notes"></textarea>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.student-behaviours.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
