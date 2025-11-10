@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Attendance Reports</h2>
      <small class="text-muted">Comprehensive attendance analytics and reporting</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('attendance.at-risk') }}" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-exclamation-triangle"></i> At-Risk
      </a>
      <a href="{{ route('attendance.consecutive') }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-calendar-x"></i> Consecutive
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <ul class="nav nav-pills mb-4" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#classTab" type="button">
        <i class="bi bi-people"></i> Class/Stream Report
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="pill" data-bs-target="#studentTab" type="button">
        <i class="bi bi-person"></i> Student Report
      </button>
    </li>
  </ul>

  <div class="tab-content">
    {{-- CLASS/STREAM REPORT --}}
    <div class="tab-pane fade show active" id="classTab">
      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Class</label>
              <select name="class" class="form-select" onchange="this.form.submit()">
                <option value="">All Classes</option>
                @foreach($classes as $id => $name)
                  <option value="{{ $id }}" @selected($selectedClass==$id)>{{ $name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Stream</label>
              <select name="stream" class="form-select" {{ $streams->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                <option value="">All Streams</option>
                @foreach($streams as $id => $name)
                  <option value="{{ $id }}" @selected($selectedStream==$id)>{{ $name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Start Date</label>
              <input type="date" name="start" class="form-control" value="{{ $startDate }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
              <label class="form-label">End Date</label>
              <input type="date" name="end" class="form-control" value="{{ $endDate }}" onchange="this.form.submit()">
            </div>
          </form>
        </div>
      </div>

      {{-- Summary Cards --}}
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-success">
            <div class="card-body text-center">
              <div class="display-6 text-success mb-2">{{ $summary['totals']['present'] ?? 0 }}</div>
              <div class="text-muted small">Present</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-danger">
            <div class="card-body text-center">
              <div class="display-6 text-danger mb-2">{{ $summary['totals']['absent'] ?? 0 }}</div>
              <div class="text-muted small">Absent</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-warning">
            <div class="card-body text-center">
              <div class="display-6 text-warning mb-2">{{ $summary['totals']['late'] ?? 0 }}</div>
              <div class="text-muted small">Late</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-primary">
            <div class="card-body text-center">
              @php
                $total = max(1, $summary['totals']['all'] ?? 0);
                $pct = round((($summary['totals']['present'] ?? 0) / $total) * 100, 1);
              @endphp
              <div class="display-6 text-primary mb-2">{{ $pct }}%</div>
              <div class="text-muted small">Attendance Rate</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Gender Breakdown --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-gender-ambiguous"></i> Gender Breakdown</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            @foreach(['male'=>'Boys','female'=>'Girls','other'=>'Other'] as $gKey => $gLabel)
            <div class="col-md-4">
              <div class="card">
                <div class="card-header bg-light">
                  <strong>{{ $gLabel }}</strong>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Present:</span>
                    <span class="badge bg-success">{{ $summary['gender'][$gKey]['present'] ?? 0 }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Absent:</span>
                    <span class="badge bg-danger">{{ $summary['gender'][$gKey]['absent'] ?? 0 }}</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>Late:</span>
                    <span class="badge bg-warning text-dark">{{ $summary['gender'][$gKey]['late'] ?? 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Daily Records --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-calendar3"></i> Daily Attendance Records</h5>
          <span class="badge bg-secondary">{{ $groupedByDate->count() }} day(s)</span>
        </div>
        <div class="card-body">
          @forelse($groupedByDate as $date => $items)
            <div class="card mb-3">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <strong>{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</strong>
                <span class="badge bg-info">{{ $items->count() }} record(s)</span>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Student</th>
                        <th>Admission #</th>
                        <th>Class</th>
                        <th>Stream</th>
                        <th class="text-center">Status</th>
                        <th>Reason Code</th>
                        <th>Reason</th>
                        <th>Marked By</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($items as $a)
                        <tr>
                          <td>{{ $a->student->full_name ?? 'Unknown' }}</td>
                          <td class="fw-semibold">{{ $a->student->admission_number ?? '—' }}</td>
                          <td>{{ $a->student->classroom->name ?? '—' }}</td>
                          <td>{{ $a->student->stream->name ?? '—' }}</td>
                          <td class="text-center">
                            <span class="badge bg-{{ $a->status == 'present' ? 'success' : ($a->status == 'late' ? 'warning text-dark' : 'danger') }}">
                              {{ ucfirst($a->status) }}
                            </span>
                            @if($a->is_excused)
                              <span class="badge bg-info ms-1">Excused</span>
                            @endif
                            @if($a->is_medical_leave)
                              <span class="badge bg-danger ms-1">Medical</span>
                            @endif
                          </td>
                          <td>{{ $a->reasonCode->name ?? '—' }}</td>
                          <td class="text-muted small">{{ Str::limit($a->reason ?? '—', 30) }}</td>
                          <td class="small">{{ $a->markedBy->name ?? '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @empty
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i> No attendance records found for the selected period.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- STUDENT REPORT --}}
    <div class="tab-pane fade" id="studentTab">
      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" class="row g-3">
            <input type="hidden" name="start" value="{{ $startDate }}">
            <input type="hidden" name="end" value="{{ $endDate }}">
            <div class="col-md-6">
              <label class="form-label">Select Student</label>
              <div class="input-group">
                <input type="hidden" id="selectedStudentId" name="student_id" value="{{ $student->id ?? '' }}">
                <input type="text" id="selectedStudentName" class="form-control" 
                       placeholder="Search by name or admission number" 
                       value="{{ $student ? ($student->full_name.' ('.$student->admission_number.')') : '' }}" readonly>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                  <i class="bi bi-search"></i> Search
                </button>
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-arrow-right-circle"></i> Load Report
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Start Date</label>
              <input type="date" name="start" value="{{ $startDate }}" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">End Date</label>
              <input type="date" name="end" value="{{ $endDate }}" class="form-control">
            </div>
          </form>
        </div>
      </div>

      @if($student)
        {{-- Student Summary --}}
        <div class="row g-3 mb-4">
          <div class="col-md-12">
            <div class="card border-primary">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                  <i class="bi bi-person-circle"></i> {{ $student->full_name }}
                  <small class="ms-2">({{ $student->admission_number }})</small>
                </h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p class="mb-1"><strong>Class:</strong> {{ $student->classroom->name ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Stream:</strong> {{ $student->stream->name ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Gender:</strong> {{ $student->gender ?? 'N/A' }}</p>
                  </div>
                  <div class="col-md-6">
                    <p class="mb-1"><strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
                    <p class="mb-0"><strong>Total Records:</strong> {{ $studentRecords->count() }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="card border-success">
              <div class="card-body text-center">
                <div class="display-6 text-success mb-2">{{ $studentStats['present'] ?? 0 }}</div>
                <div class="text-muted small">Present Days</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-danger">
              <div class="card-body text-center">
                <div class="display-6 text-danger mb-2">{{ $studentStats['absent'] ?? 0 }}</div>
                <div class="text-muted small">Absent Days</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-warning">
              <div class="card-body text-center">
                <div class="display-6 text-warning mb-2">{{ $studentStats['late'] ?? 0 }}</div>
                <div class="text-muted small">Late Days</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-primary">
              <div class="card-body text-center">
                <div class="display-6 text-primary mb-2">{{ $studentStats['percent'] ?? 0 }}%</div>
                <div class="text-muted small">Attendance %</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Detailed Records --}}
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detailed Attendance Records</h5>
            <a href="{{ route('attendance.student-analytics', $student) }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-graph-up"></i> View Full Analytics
            </a>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th class="text-center">Status</th>
                    <th>Reason Code</th>
                    <th>Reason</th>
                    <th>Marked By</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($studentRecords as $r)
                    <tr>
                      <td class="fw-semibold">{{ $r->date->format('M d, Y') }}</td>
                      <td>{{ $r->date->format('l') }}</td>
                      <td class="text-center">
                        <span class="badge bg-{{ $r->status == 'present' ? 'success' : ($r->status == 'late' ? 'warning text-dark' : 'danger') }}">
                          {{ ucfirst($r->status) }}
                        </span>
                        @if($r->is_excused)
                          <span class="badge bg-info ms-1">Excused</span>
                        @endif
                        @if($r->is_medical_leave)
                          <span class="badge bg-danger ms-1">Medical</span>
                        @endif
                      </td>
                      <td>{{ $r->reasonCode->name ?? '—' }}</td>
                      <td class="text-muted small">{{ Str::limit($r->reason ?? '—', 40) }}</td>
                      <td class="small">{{ $r->markedBy->name ?? '—' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">
                        No attendance records found for this student in the selected period.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @else
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> Please select a student to view their attendance report.
        </div>
      @endif
    </div>
  </div>
</div>

{{-- Student Search Modal --}}
<div class="modal fade" id="studentSearchModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="studentSearchInput" class="form-control mb-3" placeholder="Type name or admission number...">
        <div id="studentSearchResults" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const input = document.getElementById('studentSearchInput');
  const list = document.getElementById('studentSearchResults');
  
  if (input) {
    let timer = null;
    input.addEventListener('keyup', function() {
      clearTimeout(timer);
      const q = this.value.trim();
      if (q.length < 2) { list.innerHTML = ''; return; }
      
      timer = setTimeout(() => {
        fetch("{{ route('students.search') }}?q=" + encodeURIComponent(q))
          .then(r => r.json())
          .then(rows => {
            list.innerHTML = rows.length
              ? rows.map(s => `
                <a href="#" class="list-group-item list-group-item-action pick" 
                   data-id="${s.id}" 
                   data-name="${s.full_name}" 
                   data-adm="${s.admission_number}">
                  <div class="fw-semibold">${s.full_name}</div>
                  <small class="text-muted">${s.admission_number} | ${s.classroom_name || 'No Class'}</small>
                </a>
              `).join('')
              : '<div class="list-group-item text-muted">No results found</div>';
            
            document.querySelectorAll('.pick').forEach(el => {
              el.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('selectedStudentId').value = el.dataset.id;
                document.getElementById('selectedStudentName').value = `${el.dataset.name} (${el.dataset.adm})`;
                bootstrap.Modal.getInstance(document.getElementById('studentSearchModal')).hide();
              });
            });
          });
      }, 400);
    });
  }
});
</script>
@endpush
@endsection
