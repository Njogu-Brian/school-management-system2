@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Cards</div>
        <h1 class="mb-1">Generate Report Cards</h1>
        <p class="text-muted mb-0">Create/update report cards for a class & term.</p>
      </div>
    </div>

    <div class="alert alert-soft alert-info border-0">
      This creates/updates report cards for the selected class & term by averaging all exams in the term.
    </div>

    <form method="post" action="{{ route('academics.report_cards.generate') }}" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
              <option value="">-- choose --</option>
              @foreach($years as $y)
                <option value="{{ $y->id }}">{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              <option value="">-- choose --</option>
              @foreach($terms as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select" required>
              <option value="">-- choose --</option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream (optional)</label>
            <select name="stream_id" class="form-select">
              <option value="">All streams</option>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
        <a href="{{ route('academics.assessments.term') }}" class="btn btn-ghost-strong">View Term Assessment</a>
        <button class="btn btn-settings-primary"><i class="bi bi-gear"></i> Generate Now</button>
      </div>
    </form>
  </div>
</div>
@endsection
