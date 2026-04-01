@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div class="crumb"><a href="{{ route('academics.exams.grading.index') }}">Class grading schemes</a></div>
      <h1 class="mb-1">Apply scheme to multiple classes</h1>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form method="post" action="{{ route('academics.exams.grading.bulk-apply') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Grading scheme</label>
            <select name="grading_scheme_id" class="form-select" required>
              @foreach($schemes as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Classes</label>
            <select name="classroom_ids[]" class="form-select" multiple size="12" required>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select several classes.</div>
          </div>
          <button type="submit" class="btn btn-settings-primary">Apply</button>
          <a href="{{ route('academics.exams.grading.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
