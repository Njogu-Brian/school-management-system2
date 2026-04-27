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
        <h1 class="mb-1">Teacher load</h1>
        <p class="text-muted mb-0">Weekly load from the published whole-school run (if available).</p>
      </div>
      <a href="{{ route('academics.timetable.whole-school') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Whole-school</a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
              @foreach($years as $y)
                <option value="{{ $y->id }}" {{ $selectedYear && $selectedYear->id == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ $selectedTerm && $selectedTerm->id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Load</button>
          </div>
        </form>
      </div>
    </div>

    @if(!$run)
      <div class="alert alert-info">No published whole-school run found for the selected term/year.</div>
    @else
      <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Published run #{{ $run->id }}</strong>
          <span class="text-muted small">{{ $rows->count() }} teachers</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Teacher</th>
                  <th class="text-center">Lessons</th>
                  <th class="text-center">Cap</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($rows as $r)
                  <tr>
                    <td class="fw-semibold">{{ $r['teacher_name'] }}</td>
                    <td class="text-center">{{ $r['count'] }}</td>
                    <td class="text-center">{{ $r['cap'] }}</td>
                    <td>
                      @if($r['ok'])
                        <span class="pill-badge pill-success">OK</span>
                      @else
                        <span class="pill-badge pill-danger">Over</span>
                      @endif
                    </td>
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

