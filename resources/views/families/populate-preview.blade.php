@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb"><a href="{{ route('families.index') }}" class="text-muted text-decoration-none">Families</a></div>
        <h1 class="mb-1">Fix Blank Fields – Preview</h1>
        <p class="text-muted mb-0">Review proposed changes. For conflicts, choose which value to keep. Then click Apply.</p>
      </div>
      <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <form action="{{ route('families.populate') }}" method="POST" id="fixBlankForm">
      @csrf

      @if(!empty($familyFills))
      <div class="settings-card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Family-level updates</h5>
          <span class="pill-badge pill-secondary">Blanks filled from any sibling's parent</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Family</th>
                  <th>Field</th>
                  <th>Value to set</th>
                </tr>
              </thead>
              <tbody>
                @foreach($familyFills as $item)
                  @foreach($item['changes'] as $field => $value)
                    <tr>
                      <td>Family #{{ $item['family']->id }} ({{ $item['family']->guardian_name ?: '—' }})</td>
                      <td><code>{{ str_replace('_', ' ', $field) }}</code></td>
                      <td>{{ $value ?: '—' }}</td>
                    </tr>
                  @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endif

      @if(!empty($studentFills))
      <div class="settings-card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Parent record updates (blank → copy from sibling)</h5>
          <span class="pill-badge pill-success">No conflict</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Family</th>
                  <th>Student</th>
                  <th>Field</th>
                  <th>Value to copy</th>
                </tr>
              </thead>
              <tbody>
                @foreach($studentFills as $item)
                  <tr>
                    <td>Family #{{ $item['family']->id }}</td>
                    <td>{{ $item['student']->full_name }} ({{ $item['student']->admission_number }})</td>
                    <td><code>{{ str_replace('_', ' ', $item['field']) }}</code></td>
                    <td>{{ $item['value'] ?: '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endif

      @if(!empty($conflicts))
      <div class="settings-card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Conflicts – choose one value per field</h5>
          <span class="pill-badge pill-warning">Different values in same family</span>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">For each row, choose "Keep as is" to leave each student's value unchanged, or pick one student's value to apply to all siblings in that family for that field.</p>
          @foreach($conflicts as $idx => $conflict)
            <div class="border rounded p-3 mb-3 bg-light">
              <strong>Family #{{ $conflict['family']->id }}</strong> · <code>{{ str_replace('_', ' ', $conflict['field']) }}</code>
              <div class="mt-2">
                @php
                  $resolutionKey = $conflict['family']->id . '_' . $conflict['field'];
                @endphp
                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <label class="d-flex align-items-center gap-2 mb-0">
                    <input type="radio" name="resolutions[{{ $resolutionKey }}]" value="keep" checked>
                    <span>Keep as is (no change)</span>
                  </label>
                  @foreach($conflict['students'] as $student)
                    @php $val = $conflict['values'][$student->id] ?? ''; @endphp
                    @if(trim((string)$val) !== '')
                      <label class="d-flex align-items-center gap-2 mb-0">
                        <input type="radio" name="resolutions[{{ $resolutionKey }}]" value="{{ $student->id }}">
                        <span>Use <strong>{{ $student->full_name }}</strong>: {{ Str::limit($val, 40) }}</span>
                      </label>
                    @endif
                  @endforeach
                </div>
              </div>
              <div class="mt-2 small text-muted">
                @foreach($conflict['students'] as $student)
                  <span class="me-2">{{ $student->full_name }} ({{ $student->admission_number }}): {{ Str::limit($conflict['values'][$student->id] ?? '—', 30) }}</span>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
      @endif

      <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-settings-primary">
          <i class="bi bi-check2-circle"></i> Apply fix blank fields
        </button>
        <a href="{{ route('families.index') }}" class="btn btn-ghost-strong">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
