@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">School Transport</h2>
      <small class="text-muted">View transport information for students in your assigned classes</small>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Classroom</label>
          <select name="classroom_id" class="form-select">
            <option value="">All Classes</option>
            @foreach($classrooms as $classroom)
              <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filter
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Students with Transport --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-truck"></i> Students Using Transport ({{ $students->total() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Student</th>
              <th>Class</th>
              <th>Morning Trip</th>
              <th>Morning Drop-off</th>
              <th>Evening Trip</th>
              <th>Evening Drop-off</th>
              <th>Vehicle</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($students as $student)
              @php
                $assignment = $student->assignments->first();
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold">{{ $student->full_name }}</div>
                  <small class="text-muted">{{ $student->admission_number }}</small>
                </td>
                <td>{{ $student->classroom->name ?? '—' }}</td>
                <td>
                  @if($assignment && $assignment->morningTrip)
                    <span class="badge bg-info">{{ $assignment->morningTrip->name ?? '—' }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($assignment && $assignment->morningDropOffPoint)
                    {{ $assignment->morningDropOffPoint->name ?? '—' }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($assignment && $assignment->eveningTrip)
                    <span class="badge bg-warning text-dark">{{ $assignment->eveningTrip->name ?? '—' }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($assignment && $assignment->eveningDropOffPoint)
                    {{ $assignment->eveningDropOffPoint->name ?? '—' }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($student->vehicle)
                    <span class="badge bg-success">{{ $student->vehicle->registration_number ?? '—' }}</span>
                  @elseif($assignment && $assignment->morningTrip && $assignment->morningTrip->vehicle)
                    <span class="badge bg-success">{{ $assignment->morningTrip->vehicle->registration_number ?? '—' }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('teacher.transport.show', $student) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Details
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No students using transport found in your assigned classes.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($students->hasPages())
      <div class="card-footer">
        {{ $students->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

