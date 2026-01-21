@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">{{ $student->full_name }}</h2>
      <small class="text-muted">Student Details</small>
    </div>
    <a href="{{ route('teacher.students.index') }}" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="row g-3">
    {{-- Basic Information --}}
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-person"></i> Basic Information</h5>
        </div>
        <div class="card-body">
          <table class="table table-borderless mb-0">
            <tr>
              <th width="150">Admission #:</th>
              <td><span class="badge bg-primary">{{ $student->admission_number }}</span></td>
            </tr>
            <tr>
              <th>Class:</th>
              <td>{{ $student->classroom->name ?? '—' }}</td>
            </tr>
            <tr>
              <th>Stream:</th>
              <td>{{ $student->stream->name ?? '—' }}</td>
            </tr>
            <tr>
              <th>Gender:</th>
              <td>{{ ucfirst($student->gender ?? '—') }}</td>
            </tr>
            <tr>
              <th>Date of Birth:</th>
              <td>{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '—' }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    {{-- Parent Information --}}
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-people"></i> Parent Information</h5>
        </div>
        <div class="card-body">
          @if($student->parent)
            @php $parent = $student->parent; @endphp
            <table class="table table-borderless mb-0">
              <tr>
                <th width="170">Primary Contact</th>
                <td>{{ $parent->primary_contact_name ?? '—' }}</td>
              </tr>
              <tr>
                <th>Primary Phone</th>
                <td>{{ $parent->primary_contact_phone ?? '—' }}</td>
              </tr>
              <tr>
                <th>Primary Email</th>
                <td>{{ $parent->primary_contact_email ?? '—' }}</td>
              </tr>
              <tr class="table-light">
                <th colspan="2" class="text-uppercase small">Father</th>
              </tr>
              <tr>
                <th>Name</th>
                <td>{{ $parent->father_name ?? '—' }}</td>
              </tr>
              <tr>
                <th>Phone</th>
                <td>{{ $parent->father_phone ?? '—' }}</td>
              </tr>
              <tr>
                <th>Email</th>
                <td>{{ $parent->father_email ?? '—' }}</td>
              </tr>
              <tr class="table-light">
                <th colspan="2" class="text-uppercase small">Mother</th>
              </tr>
              <tr>
                <th>Name</th>
                <td>{{ $parent->mother_name ?? '—' }}</td>
              </tr>
              <tr>
                <th>Phone</th>
                <td>{{ $parent->mother_phone ?? '—' }}</td>
              </tr>
              <tr>
                <th>Email</th>
                <td>{{ $parent->mother_email ?? '—' }}</td>
              </tr>
              <tr class="table-light">
                <th colspan="2" class="text-uppercase small">Guardian</th>
              </tr>
              <tr>
                <th>Name</th>
                <td>{{ $parent->guardian_name ?? '—' }}</td>
              </tr>
              <tr>
                <th>Phone</th>
                <td>{{ $parent->guardian_phone ?? '—' }}</td>
              </tr>
              <tr>
                <th>Email</th>
                <td>{{ $parent->guardian_email ?? '—' }}</td>
              </tr>
            </table>
          @else
            <p class="text-muted mb-0">No parent information available</p>
          @endif
        </div>
      </div>
    </div>

    {{-- Recent Attendance --}}
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Recent Attendance</h5>
        </div>
        <div class="card-body">
          @forelse($student->attendances->take(5) as $attendance)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                <span class="fw-semibold">{{ $attendance->date->format('d M Y') }}</span>
                <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'absent' ? 'danger' : 'warning') }}">
                  {{ ucfirst($attendance->status) }}
                </span>
              </div>
            </div>
          @empty
            <p class="text-muted mb-0">No attendance records</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Recent Exam Marks --}}
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-journal-check"></i> Recent Exam Marks</h5>
        </div>
        <div class="card-body">
          @forelse($recentMarks->take(5) as $mark)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                <span class="fw-semibold">{{ $mark->subject->name ?? '—' }}</span>
                <small class="text-muted d-block">{{ $mark->exam->name ?? '—' }}</small>
              </div>
              <span class="badge bg-info">{{ $mark->score_moderated ?? $mark->score_raw ?? '—' }}</span>
            </div>
          @empty
            <p class="text-muted mb-0">No exam marks</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Recent Homework --}}
    <div class="col-md-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-journal"></i> Recent Homework</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Subject</th>
                  <th>Due Date</th>
                  <th>Status</th>
                  <th>Score</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentHomework->take(10) as $homework)
                  <tr>
                    <td>{{ $homework->homework->title ?? '—' }}</td>
                    <td>{{ $homework->homework->subject->name ?? '—' }}</td>
                    <td>{{ $homework->homework->due_date ? \Carbon\Carbon::parse($homework->homework->due_date)->format('d M Y') : '—' }}</td>
                    <td>
                      <span class="badge bg-{{ $homework->status === 'submitted' ? 'success' : 'warning' }}">
                        {{ ucfirst($homework->status) }}
                      </span>
                    </td>
                    <td>{{ $homework->score ?? '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">No homework records</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

