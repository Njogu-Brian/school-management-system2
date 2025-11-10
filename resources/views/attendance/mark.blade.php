@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Mark Attendance</h2>
      <small class="text-muted">Record student attendance for the selected date</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('attendance.at-risk') }}" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-exclamation-triangle"></i> At-Risk Students
      </a>
      <a href="{{ route('attendance.consecutive') }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-calendar-x"></i> Consecutive Absences
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filter Bar --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('attendance.mark.form') }}" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Class</label>
          <select name="class" id="classSelect" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Class --</option>
            @foreach($classes as $id => $name)
              <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Stream</label>
          <select name="stream" id="streamSelect" class="form-select" {{ $streams->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
            <option value="">-- All Streams --</option>
            @foreach($streams as $id => $name)
              <option value="{{ $id }}" {{ $selectedStream == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit()">
        </div>

        <div class="col-md-4">
          <label class="form-label">Search (Name or Admission #)</label>
          <div class="input-group">
            <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="e.g. 01523 or Ann">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  @if ($students->isNotEmpty())
  <form id="attendanceForm" method="POST" action="{{ route('attendance.mark') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate }}">

    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex gap-2">
            <button type="button" id="btnMarkAllPresent" class="btn btn-outline-success btn-sm">
              <i class="bi bi-check-all"></i> Mark All Present
            </button>
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
              <i class="bi bi-gear"></i> Advanced Options
            </button>
          </div>
          <div class="small text-muted">
            <i class="bi bi-info-circle"></i> Unmarked: <strong>{{ $unmarkedCount }}</strong>
          </div>
        </div>

        {{-- Advanced Options Toggle --}}
        <div class="collapse mt-3" id="advancedOptions">
          <div class="alert alert-info mb-0">
            <h6 class="mb-2"><i class="bi bi-info-circle"></i> Advanced Options</h6>
            <div class="row g-2">
              <div class="col-md-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="enableSubjectTracking">
                  <label class="form-check-label" for="enableSubjectTracking">Enable Subject-wise Attendance</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle" id="attendanceTable">
            <thead class="table-light">
              <tr>
                <th style="width:50px;">#</th>
                <th>Admission #</th>
                <th>Name</th>
                <th style="width:280px;">Status</th>
                <th style="width:200px;">Preset Reason</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @php $row = 0; @endphp
              @foreach ($students as $student)
                @php
                  $row++;
                  $att = $attendanceRecords->get($student->id);
                  $status = $att ? $att->status : null;
                  $reasonVal = $att ? $att->reason : '';
                  $reasonCodeId = $att ? $att->reason_code_id : null;
                  $isExcused = $att ? $att->is_excused : false;
                  $isMedicalLeave = $att ? $att->is_medical_leave : false;
                  $excuseNotes = $att ? $att->excuse_notes : '';
                  $subjectId = $att ? $att->subject_id : null;
                  $periodNumber = $att ? $att->period_number : null;
                  $periodName = $att ? $att->period_name : '';
                  $consecutive = $att ? $att->consecutive_absence_count : 0;
                @endphp
                <tr data-student-id="{{ $student->id }}" data-search="{{ strtolower($student->admission_number.' '.$student->first_name.' '.$student->middle_name.' '.$student->last_name) }}">
                  <td>{{ $row }}</td>
                  <td class="fw-semibold">{{ $student->admission_number }}</td>
                  <td>
                    <div>{{ $student->full_name }}</div>
                    @if($consecutive > 0)
                      <small class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i> {{ $consecutive }} consecutive absence(s)
                      </small>
                    @endif
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="present" id="present_{{ $student->id }}" {{ $status === 'present' ? 'checked' : '' }}>
                      <label class="btn btn-outline-success" for="present_{{ $student->id }}">Present</label>

                      <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="absent" id="absent_{{ $student->id }}" {{ $status === 'absent' ? 'checked' : '' }}>
                      <label class="btn btn-outline-danger" for="absent_{{ $student->id }}">Absent</label>

                      <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="late" id="late_{{ $student->id }}" {{ $status === 'late' ? 'checked' : '' }}>
                      <label class="btn btn-outline-warning" for="late_{{ $student->id }}">Late</label>
                    </div>
                  </td>
                  <td>
                    <select name="reason_code_{{ $student->id }}" class="form-select form-select-sm reason-code-select" {{ $status === 'present' ? 'disabled' : '' }}>
                      <option value="">Select Preset Reason</option>
                      @foreach($reasonCodes as $code)
                        <option value="{{ $code->id }}" @selected($reasonCodeId == $code->id)>{{ $code->name }}</option>
                      @endforeach
                    </select>
                    <input type="hidden" name="reason_{{ $student->id }}" value="{{ $reasonVal }}">
                  </td>
                  <td>
                    <textarea name="excuse_notes_{{ $student->id }}" class="form-control form-control-sm" rows="2" placeholder="Notes..." {{ $status === 'present' ? 'disabled' : '' }}>{{ $excuseNotes }}</textarea>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Total Students: <strong>{{ $students->count() }}</strong>
        </div>
        <button type="button" id="btnOpenSummary" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Submit Attendance
        </button>
      </div>
    </div>
  </form>
  @else
    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i> No students found for this selection. Please select a class and date.
    </div>
  @endif
</div>

{{-- Summary Modal --}}
<div class="modal fade" id="summaryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Attendance Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Date:</strong> {{ $selectedDate }}</p>
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="card border-success">
              <div class="card-body text-center">
                <h4 class="mb-0 text-success" id="sumPresent">0</h4>
                <small class="text-muted">Present</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-danger">
              <div class="card-body text-center">
                <h4 class="mb-0 text-danger" id="sumAbsent">0</h4>
                <small class="text-muted">Absent</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-warning">
              <div class="card-body text-center">
                <h4 class="mb-0 text-warning" id="sumLate">0</h4>
                <small class="text-muted">Late</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-secondary">
              <div class="card-body text-center">
                <h4 class="mb-0" id="sumTotal">0</h4>
                <small class="text-muted">Total</small>
              </div>
            </div>
          </div>
        </div>
        <div id="absentListWrap" style="display:none;">
          <strong>Absent Students:</strong>
          <ul id="absentList" class="mb-0"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btnConfirmSubmit" class="btn btn-primary">Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Toggle subject tracking
  document.getElementById('enableSubjectTracking')?.addEventListener('change', function() {
    document.querySelectorAll('.subject-tracking').forEach(el => {
      el.style.display = this.checked ? 'block' : 'none';
    });
  });

  // Enable/disable fields based on status
  document.querySelectorAll('.mark-radio').forEach(r => {
    r.addEventListener('change', function() {
      const studentId = this.name.split('_')[1];
      const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
      const isPresent = this.value === 'present';
      
      // Toggle disabled state for all related fields
      row.querySelectorAll('.reason-code-select, .reason-input, textarea[name^="excuse"], input[type="checkbox"][name^="is_"]').forEach(field => {
        field.disabled = isPresent;
        if (isPresent && (field.type === 'text' || field.tagName === 'TEXTAREA')) {
          field.value = '';
        }
      });
    });
  });

  // Auto-set medical/excused based on reason code
  document.querySelectorAll('.reason-code-select').forEach(select => {
    select.addEventListener('change', function() {
      const studentId = this.name.split('_')[2];
      const reasonCodeId = this.value;
      
      if (reasonCodeId) {
        // Fetch reason code details (you could use AJAX or pass data)
        // For now, we'll check if it's a medical code by checking the option text
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.text.toLowerCase().includes('medical') || selectedOption.text.toLowerCase().includes('sick')) {
          document.getElementById(`medical_${studentId}`).checked = true;
          document.getElementById(`excused_${studentId}`).checked = true;
        }
      }
    });
  });

  // Mark all present
  document.getElementById('btnMarkAllPresent')?.addEventListener('click', function() {
    document.querySelectorAll('.mark-radio[value="present"]').forEach(r => {
      r.checked = true;
      r.dispatchEvent(new Event('change'));
    });
  });

  // Summary modal
  document.getElementById('btnOpenSummary')?.addEventListener('click', function() {
    const rows = Array.from(document.querySelectorAll('#attendanceTable tbody tr'));
    let total = 0, present = 0, absent = 0, late = 0;
    const absentNames = [];

    rows.forEach(tr => {
      const checked = tr.querySelector('.mark-radio:checked');
      if (!checked) return;
      total++;
      if (checked.value === 'present') present++;
      else if (checked.value === 'absent') {
        absent++;
        absentNames.push(tr.children[2].innerText.trim().split('\n')[0]);
      }
      else if (checked.value === 'late') late++;
    });

    document.getElementById('sumTotal').innerText = total;
    document.getElementById('sumPresent').innerText = present;
    document.getElementById('sumAbsent').innerText = absent;
    document.getElementById('sumLate').innerText = late;

    const wrap = document.getElementById('absentListWrap');
    const list = document.getElementById('absentList');
    list.innerHTML = '';
    if (absent > 0) {
      wrap.style.display = 'block';
      absentNames.slice(0, 20).forEach(n => {
        const li = document.createElement('li');
        li.textContent = n;
        list.appendChild(li);
      });
      if (absentNames.length > 20) {
        const li = document.createElement('li');
        li.textContent = `... and ${absentNames.length - 20} more`;
        list.appendChild(li);
      }
    } else {
      wrap.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('summaryModal')).show();
  });

  // Confirm submit
  document.getElementById('btnConfirmSubmit')?.addEventListener('click', function() {
    document.getElementById('attendanceForm').submit();
  });
});
</script>
@endsection
