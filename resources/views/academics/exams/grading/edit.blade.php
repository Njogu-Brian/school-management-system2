@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div class="crumb"><a href="{{ route('academics.exams.grading.index') }}">Class grading schemes</a></div>
      <h1 class="mb-1">{{ $classroom->name }}</h1>
      <p class="text-muted mb-0">Choose which grading scheme applies to this class.</p>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="post" action="{{ route('academics.exams.grading.update', $classroom) }}" class="row g-3 align-items-end">
          @csrf
          @method('PUT')
          <div class="col-md-8">
            <label class="form-label">Grading scheme</label>
            <select name="grading_scheme_id" class="form-select">
              <option value="">Use system default only</option>
              @foreach($schemes as $s)
                <option value="{{ $s->id }}" @selected($mapping && (int)$mapping->grading_scheme_id === (int)$s->id)>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-settings-primary">Save</button>
            <a href="{{ route('academics.exams.grading.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    @if($bands->isNotEmpty())
      <div class="settings-card">
        <div class="card-header"><h5 class="mb-0">Preview ({{ optional($schemes->firstWhere('id', $previewSchemeId))->name ?? 'scheme' }})</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern mb-0">
              <thead class="table-light"><tr><th>% Min</th><th>% Max</th><th>Grade</th><th>Points</th></tr></thead>
              <tbody>
                @foreach($bands as $b)
                  <tr>
                    <td>{{ $b->min }}</td>
                    <td>{{ $b->max }}</td>
                    <td>{{ $b->label }}</td>
                    <td>{{ $b->rank }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
