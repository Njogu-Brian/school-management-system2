@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div class="crumb"><a href="{{ route('academics.exams.grading.index') }}">Class grading schemes</a></div>
      <h1 class="mb-1">Duplicate grading scheme</h1>
      <p class="text-muted mb-0">Copy all grade bands into a new scheme, then optionally assign it to classes.</p>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form method="post" action="{{ route('academics.exams.grading.duplicate-store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Copy from</label>
            <select name="source_scheme_id" class="form-select" required>
              @foreach($schemes as $s)
                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->bands->count() }} bands)</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">New scheme name</label>
            <input type="text" name="new_name" class="form-control" required maxlength="255" placeholder="e.g. Form 2 — 2026">
          </div>
          <div class="mb-3">
            <label class="form-label">Assign to classes (optional)</label>
            <select name="classroom_ids[]" class="form-select" multiple size="10">
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-settings-primary">Create copy</button>
          <a href="{{ route('academics.exams.grading.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
