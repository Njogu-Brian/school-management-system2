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
        <h1 class="mb-1">Whole-school timetable</h1>
        <p class="text-muted mb-0">Validate feasibility for all streams before generation.</p>
      </div>
      <a href="{{ route('academics.timetable.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Classroom view</a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="POST" action="{{ route('academics.timetable.whole-school.feasibility') }}" class="row g-3 align-items-end">
          @csrf
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
          <div class="col-md-4">
            <label class="form-label">Streams (optional)</label>
            <select name="stream_ids[]" class="form-select" multiple>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->classroom?->name ? ' · '.$s->classroom->name : '' }}</option>
              @endforeach
            </select>
            <div class="form-text">Leave empty to validate all streams.</div>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-clipboard-check"></i> Feasibility</button>
          </div>
        </form>
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-3">
      <form method="POST" action="{{ route('academics.timetable.whole-school.generate') }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $selectedYear?->id }}">
        <input type="hidden" name="term_id" value="{{ $selectedTerm?->id }}">
        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-magic"></i> Generate draft</button>
      </form>
      @if(session('generated_run_id'))
        <form method="POST" action="{{ route('academics.timetable.whole-school.publish') }}">
          @csrf
          <input type="hidden" name="run_id" value="{{ session('generated_run_id') }}">
          <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Publish</button>
        </form>
      @endif
    </div>

    @if($report)
      <div class="alert {{ $report['success'] ? 'alert-success' : 'alert-danger' }}">
        <strong>{{ $report['success'] ? 'Feasible' : 'Not feasible yet' }}</strong>
        <div class="small">Streams checked: {{ $report['meta']['stream_count'] ?? 0 }}</div>
      </div>

      <div class="settings-card mb-3">
        <div class="card-header"><strong>Streams</strong></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Stream</th>
                  <th class="text-center">Avail</th>
                  <th class="text-center">Demand</th>
                  <th class="text-center">Doubles</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($report['streams'] as $s)
                  <tr>
                    <td class="fw-semibold">{{ $s['stream_name'] }}</td>
                    <td class="text-center">{{ $s['available_lesson_slots'] ?? '-' }}</td>
                    <td class="text-center">{{ $s['total_demand_slots'] ?? '-' }}</td>
                    <td class="text-center">{{ $s['combinable_pairs'] ?? '-' }}</td>
                    <td>
                      @if($s['ok'])
                        <span class="pill-badge pill-success">OK</span>
                      @else
                        <span class="pill-badge pill-danger">Issues</span>
                        <div class="small text-muted mt-1">
                          @foreach($s['errors'] as $e)
                            <div>• {{ $e }}</div>
                          @endforeach
                        </div>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="settings-card">
        <div class="card-header"><strong>Teacher load (weekly demand vs cap)</strong></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Staff ID</th>
                  <th class="text-center">Demand</th>
                  <th class="text-center">Cap</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($report['teacher_load'] as $r)
                  <tr>
                    <td class="fw-semibold">{{ $r['staff_id'] }}</td>
                    <td class="text-center">{{ $r['demand'] }}</td>
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

