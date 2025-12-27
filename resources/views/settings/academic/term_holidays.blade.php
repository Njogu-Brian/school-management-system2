@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Settings / Academic Calendar</div>
        <h1 class="mb-1">Term Holidays</h1>
        <p class="text-muted mb-0">Review and add holidays that fall inside active term sessions. Inter-term breaks are tracked separately and won’t appear here.</p>
      </div>
      <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Back to Calendar
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show mt-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show mt-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mt-3">
      <div class="card-header">
        <h5 class="mb-0">Filters</h5>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
              @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected($academicYearId == $year->id)>{{ $year->year }} {{ $year->is_active ? '(Active)' : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" onchange="this.form.submit()">
              @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <a href="{{ route('settings.academic.term-holidays') }}" class="btn btn-ghost w-100">Reset</a>
            <form action="{{ route('settings.school-days.generate-holidays') }}" method="POST" class="w-100">
              @csrf
              <input type="hidden" name="year" value="{{ optional($academicYears->firstWhere('id', $academicYearId))->year ?? date('Y') }}">
              <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="bi bi-magic"></i> Generate Public Holidays
              </button>
            </form>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0"><i class="bi bi-umbrella"></i> Add Term Holiday</h5>
          <small class="text-muted">Holidays must fall within the selected term dates.</small>
        </div>
        @if($selectedTerm && $selectedTerm->opening_date && $selectedTerm->closing_date)
          <span class="input-chip">Term window: {{ \Carbon\Carbon::parse($selectedTerm->opening_date)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($selectedTerm->closing_date)->format('M d, Y') }}</span>
        @endif
      </div>
      <div class="card-body">
        <form action="{{ route('settings.academic.term-holidays.store') }}" method="POST" class="row g-3">
          @csrf
          <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
          <input type="hidden" name="term_id" value="{{ $termId }}">
          <div class="col-md-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
              <option value="holiday">Holiday</option>
              <option value="custom_off_day">Custom Off Day</option>
              <option value="midterm_break">Midterm Break</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Sports Day">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-settings-primary w-100">
              <i class="bi bi-save"></i> Save
            </button>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Optional notes"></textarea>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Holidays Within Term</h5>
      </div>
      <div class="card-body">
        @if(!$selectedTerm || !$selectedTerm->opening_date || !$selectedTerm->closing_date)
          <div class="alert alert-warning mb-0">
            Set opening and closing dates for the selected term to view holidays.
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Day</th>
                  <th>Type</th>
                  <th>Name</th>
                  <th>Description</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($holidays as $holiday)
                  <tr>
                    <td class="fw-semibold">{{ \Carbon\Carbon::parse($holiday->date)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($holiday->date)->format('l') }}</td>
                    <td>
                      <span class="badge bg-{{ $holiday->type === 'holiday' ? 'danger' : ($holiday->type === 'midterm_break' ? 'warning' : 'secondary') }}">
                        {{ ucfirst(str_replace('_',' ', $holiday->type)) }}
                      </span>
                    </td>
                    <td>{{ $holiday->name ?? '—' }}</td>
                    <td class="text-muted small">{{ $holiday->description ?? '—' }}</td>
                    <td class="text-end">
                      <form action="{{ route('settings.academic.term-holidays.update', $holiday) }}" method="POST" class="row g-2 align-items-center justify-content-end">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                        <input type="hidden" name="term_id" value="{{ $termId }}">
                        <div class="col-12 col-lg-3">
                          <input type="date" name="date" class="form-control form-control-sm" value="{{ \Carbon\Carbon::parse($holiday->date)->toDateString() }}">
                        </div>
                        <div class="col-12 col-lg-3">
                          <select name="type" class="form-select form-select-sm">
                            <option value="holiday" @selected($holiday->type === 'holiday')>Holiday</option>
                            <option value="custom_off_day" @selected($holiday->type === 'custom_off_day')>Custom Off Day</option>
                            <option value="midterm_break" @selected($holiday->type === 'midterm_break')>Midterm Break</option>
                          </select>
                        </div>
                        <div class="col-12 col-lg-3">
                          <input type="text" name="name" class="form-control form-control-sm" value="{{ $holiday->name }}" placeholder="Name">
                        </div>
                        <div class="col-12 col-lg-3">
                          <input type="text" name="description" class="form-control form-control-sm" value="{{ $holiday->description }}" placeholder="Description">
                        </div>
                        <div class="col-12 mt-2 d-flex justify-content-end">
                          <button type="submit" class="btn btn-sm btn-settings-primary">
                            <i class="bi bi-save"></i> Update
                          </button>
                        </div>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      No holidays recorded inside this term. Inter-term breaks are managed automatically between terms.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection


