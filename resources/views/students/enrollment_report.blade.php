@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Enrollment by Class' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Enrollment by Class</h1>
        <p class="text-muted mb-0">Boys and girls enrolled in each class for the selected term.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('students.index') }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left"></i> All Students
        </a>
        <a
          href="{{ route('students.enrollment-report.export-excel', request()->query()) }}"
          class="btn btn-ghost-strong"
        >
          <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
        </a>
        <a
          href="{{ route('students.enrollment-report.export-pdf', request()->query()) }}"
          class="btn btn-settings-primary"
        >
          <i class="bi bi-file-pdf"></i> Export PDF
        </a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">{{ $subtitle }}</p>
        </div>
        <span class="pill-badge pill-secondary">Active students</span>
      </div>
      <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('students.enrollment-report') }}">
          @include('partials.academic_year_term_selects', [
            'years' => $context['years'] ?? collect(),
            'terms' => $context['terms'] ?? collect(),
            'selectedYearId' => $context['selectedYearId'] ?? null,
            'selectedTermId' => $context['selectedTermId'] ?? null,
            'yearSelectId' => 'enrollment_report_year_id',
            'termSelectId' => 'enrollment_report_term_id',
            'yearCol' => 'col-md-3',
            'termCol' => 'col-md-3',
          ])
          <div class="col-md-3">
            <label class="form-label">Campus</label>
            <select name="campus" class="form-select">
              <option value="">All Campuses</option>
              <option value="lower" @selected($campus === 'lower')>Lower</option>
              <option value="upper" @selected($campus === 'upper')>Upper</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Enrollment Scope</label>
            <select name="current_term_only" class="form-select">
              <option value="0" @selected(empty($currentTermOnly))>All active students (includes future-term admissions)</option>
              <option value="1" @selected(!empty($currentTermOnly))>Current term only (excludes future-term admissions)</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-funnel"></i> Apply
            </button>
            <a href="{{ route('students.enrollment-report') }}" class="btn btn-ghost-strong">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted small">Total Students</div>
            <div class="fs-3 fw-bold">{{ number_format($totals['total']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted small">Boys</div>
            <div class="fs-3 fw-bold text-primary">{{ number_format($totals['boys']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted small">Girls</div>
            <div class="fs-3 fw-bold text-danger">{{ number_format($totals['girls']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted small">Other / Unspecified</div>
            <div class="fs-3 fw-bold">{{ number_format($totals['other']) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Enrollment Summary</h5>
          <p class="text-muted small mb-0">{{ count($rows) }} class{{ count($rows) === 1 ? '' : 'es' }}</p>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class</th>
                @if(!$campus)
                  <th>Campus</th>
                @endif
                <th class="text-end">Boys</th>
                <th class="text-end">Girls</th>
                <th class="text-end">Other</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                <tr>
                  <td class="fw-semibold">{{ $row['class'] }}</td>
                  @if(!$campus)
                    <td>{{ $row['campus'] ? ucfirst($row['campus']) : '—' }}</td>
                  @endif
                  <td class="text-end">{{ number_format($row['boys']) }}</td>
                  <td class="text-end">{{ number_format($row['girls']) }}</td>
                  <td class="text-end">{{ number_format($row['other']) }}</td>
                  <td class="text-end fw-semibold">{{ number_format($row['total']) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $campus ? 5 : 6 }}" class="text-center text-muted py-4">No classes found.</td>
                </tr>
              @endforelse
            </tbody>
            @if(count($rows) > 0)
              <tfoot class="table-light">
                <tr class="fw-bold">
                  <td>Grand Total</td>
                  @if(!$campus)
                    <td></td>
                  @endif
                  <td class="text-end">{{ number_format($totals['boys']) }}</td>
                  <td class="text-end">{{ number_format($totals['girls']) }}</td>
                  <td class="text-end">{{ number_format($totals['other']) }}</td>
                  <td class="text-end">{{ number_format($totals['total']) }}</td>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
