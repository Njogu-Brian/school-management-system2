@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Attendance records — ' . $votehead->name,
        'icon' => 'bi bi-journal-text',
        'subtitle' => 'Activity fee attendance (not swimming wallet attendance).',
        'actions' => '<a href="' . route('activity-fees.show', $votehead) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-people"></i> Roster</a>'
          . '<a href="' . route('activity-fees.attendance', $votehead) . '" class="btn btn-finance btn-finance-success"><i class="bi bi-calendar-check"></i> Mark attendance</a>',
    ])

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="finance-form-label">From</label>
          <input type="date" name="date_from" class="finance-form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-3">
          <label class="finance-form-label">To</label>
          <input type="date" name="date_to" class="finance-form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-finance btn-finance-primary">Filter</button>
        </div>
      </form>
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th>Marked by</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $row)
                <tr>
                  <td>{{ $row->attendance_date?->format('Y-m-d') }}</td>
                  <td>{{ $row->student?->full_name ?? '—' }}</td>
                  <td>{{ $row->student?->classroom?->name ?? '—' }}</td>
                  <td>{{ $row->markedBy?->name ?? '—' }}</td>
                  <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row->notes, 80) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No records yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($records->hasPages())
          <div class="p-3">{{ $records->links() }}</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
