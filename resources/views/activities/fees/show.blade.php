@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @php
      $headerActions = '<a href="' . route('activity-fees.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> All activities</a>'
        . '<a href="' . route('activity-fees.print', ['votehead' => $votehead->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-secondary" target="_blank"><i class="bi bi-printer"></i> Printable list</a>'
        . '<a href="' . route('activity-fees.attendance', ['votehead' => $votehead->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-success"><i class="bi bi-calendar-check"></i> Mark attendance</a>';
      if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Supervisor'])) {
        $headerActions .= '<a href="' . route('activity-fees.records', $votehead) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-journal-text"></i> Records</a>';
      }
    @endphp
    @include('finance.partials.header', [
        'title' => $votehead->name,
        'icon' => 'bi bi-people',
        'subtitle' => 'Roster for year ' . $year . ', term ' . $term . ' (billed optional fees).',
        'actions' => $headerActions,
    ])

    @include('finance.invoices.partials.alerts')

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <form method="GET" action="{{ route('activity-fees.show', $votehead) }}" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="finance-form-label">Year</label>
          <input type="number" name="year" class="finance-form-control" value="{{ $year }}" min="2000" max="2100">
        </div>
        <div class="col-md-3">
          <label class="finance-form-label">Term</label>
          <select name="term" class="finance-form-select">
            @foreach([1,2,3] as $t)
              <option value="{{ $t }}" {{ (int)$term === $t ? 'selected' : '' }}>Term {{ $t }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-finance btn-finance-primary">Apply</button>
        </div>
      </form>
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-header d-flex justify-content-between align-items-center">
        <span>{{ $students->count() }} student(s)</span>
      </div>
      <div class="finance-card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Admission</th>
                <th>Name</th>
                <th>Class</th>
              </tr>
            </thead>
            <tbody>
              @forelse($students as $i => $student)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td><strong>{{ $student->admission_number }}</strong></td>
                  <td>{{ $student->full_name }}</td>
                  <td>{{ $student->classroom->name ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">No students on the roster for this year and term.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
