@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Mark attendance — ' . $votehead->name,
        'icon' => 'bi bi-calendar-check',
        'subtitle' => 'Year ' . $year . ', term ' . $term . '. Only students on the activity roster can be marked.',
        'actions' => '<a href="' . route('activity-fees.show', ['votehead' => $votehead->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-people"></i> Roster</a>'
          . '<a href="' . route('activity-fees.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-list"></i> All activities</a>',
    ])

    @include('finance.invoices.partials.alerts')

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <form method="GET" action="{{ route('activity-fees.attendance', $votehead) }}" class="row g-3">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="term" value="{{ $term }}">
        <div class="col-md-4">
          <label class="finance-form-label">Date</label>
          <input type="date" name="date" class="finance-form-control" value="{{ $date }}" onchange="this.form.submit()">
        </div>
      </form>
    </div>

    @if($students->isNotEmpty())
    <form method="POST" action="{{ route('activity-fees.attendance.store', $votehead) }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="term" value="{{ $term }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-3">
            <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ $votehead->name }} — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h5>
                    <p class="text-muted small mb-0">{{ $students->count() }} on roster</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-finance btn-finance-outline" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = true)">Select all</button>
                    <button type="button" class="btn btn-sm btn-finance btn-finance-outline" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = false)">Clear</button>
                </div>
            </div>
            <div class="finance-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px"><input type="checkbox" id="toggleAll" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = this.checked)"></th>
                                <th>#</th>
                                <th>Admission</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php $existing = $records->get($student->id); @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox" {{ $existing ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $student->admission_number }}</strong></td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->classroom->name ?? '—' }}</td>
                                    <td>
                                        @if($existing)
                                            <span class="badge bg-success">Present</span>
                                        @else
                                            <span class="badge bg-secondary">Not marked</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <div class="finance-card-body">
                <label class="finance-form-label">Session notes (optional)</label>
                <textarea name="notes" class="finance-form-control" rows="2" placeholder="Applied to all marked present for this date">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-finance btn-finance-success"><i class="bi bi-check2-circle"></i> Save attendance</button>
        </div>
    </form>
    @else
    <div class="alert alert-info border-0">No students on the roster for this year and term. Optional fees must be billed for this votehead.</div>
    @endif
  </div>
</div>
@endsection
