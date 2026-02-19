@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Attendance</div>
        <h1 class="mb-1">Mark Attendance</h1>
        <p class="text-muted mb-0">Record student attendance for the selected date.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('attendance.at-risk') }}" class="btn btn-ghost-strong btn-sm">
          <i class="bi bi-exclamation-triangle"></i> At-Risk Students
        </a>
        <a href="{{ route('attendance.consecutive') }}" class="btn btn-ghost-strong btn-sm">
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

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Class, stream, date, and quick search.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('attendance.mark.form') }}" class="row g-3">
          @if(($showCampusFilter ?? false) && !$classes->isEmpty())
          <div class="col-md-2">
            <label class="form-label">Campus</label>
            <select name="campus" class="form-select" onchange="this.form.submit()">
              <option value="">-- All Campuses --</option>
              <option value="upper" {{ ($selectedCampus ?? '') === 'upper' ? 'selected' : '' }}>Upper (Creche–Grade 3)</option>
              <option value="lower" {{ ($selectedCampus ?? '') === 'lower' ? 'selected' : '' }}>Lower (Grade 4–9)</option>
            </select>
          </div>
          @endif
          <div class="col-md-2">
            <label class="form-label">Class</label>
            <select name="class" id="classSelect" class="form-select" onchange="this.form.submit()">
              <option value="">-- Select Class --</option>
              @foreach($classes as $id => $name)
                <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
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

          <div class="col-md-2">
            <label class="form-label">Show</label>
            <select name="marked_filter" class="form-select" onchange="this.form.submit()">
              <option value="all" {{ ($markedFilter ?? 'all') === 'all' ? 'selected' : '' }}>All students</option>
              <option value="marked" {{ ($markedFilter ?? '') === 'marked' ? 'selected' : '' }}>Marked only</option>
              <option value="unmarked" {{ ($markedFilter ?? '') === 'unmarked' ? 'selected' : '' }}>Unmarked only</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Search (Name or Admission #)</label>
            <div class="input-group">
              <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="e.g. 01523 or Ann">
              <button type="submit" class="btn btn-settings-primary"><i class="bi bi-search"></i></button>
            </div>
          </div>
        </form>
      </div>
    </div>

    @if ($students->isNotEmpty())
    <form id="attendanceForm" method="POST" action="{{ route('attendance.mark') }}">
      @csrf
      <input type="hidden" name="date" value="{{ $selectedDate }}">
      <input type="hidden" name="class" value="{{ $selectedClass }}">
      <input type="hidden" name="stream" value="{{ $selectedStream }}">

      <div class="settings-card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex gap-2">
            <button type="button" id="btnMarkAllPresent" class="btn btn-ghost-strong btn-sm">
              <i class="bi bi-check-all"></i> Mark All Present
            </button>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="pill-badge pill-secondary">Unmarked: {{ $unmarkedCount }}</span>
            <span class="pill-badge pill-info">Date: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</span>
          </div>
        </div>
      </div>

      <div class="settings-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0" id="attendanceTable">
              <thead class="table-light">
                <tr>
                  <th style="width:50px;">#</th>
                  <th>Admission #</th>
                  <th>Name</th>
                  <th style="width:280px;">Status</th>
                  <th style="width:220px;">Preset Reason</th>
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
                    $reasonCodeId = $att ? $att->reason_code_id : null;
                    $excuseNotes = $att ? $att->excuse_notes : '';
                    $consecutive = $att ? $att->consecutive_absence_count : 0;
                  @endphp
                  <tr data-student-id="{{ $student->id }}" data-search="{{ strtolower($student->admission_number.' '.$student->first_name.' '.$student->middle_name.' '.$student->last_name) }}">
                    <td>{{ $row }}</td>
                    <td class="fw-semibold">{{ $student->admission_number }}</td>
                    <td>
                      <div>{{ $student->full_name }}</div>
                      @if($consecutive > 0)
                        <small class="text-danger d-inline-flex align-items-center gap-1">
                          <i class="bi bi-exclamation-triangle"></i> {{ $consecutive }} consecutive absence(s)
                        </small>
                      @endif
                    </td>
                    <td>
                      @if($canUnmark ?? false)
                        <input type="hidden" name="unmark_{{ $student->id }}" class="unmark-input" data-student-id="{{ $student->id }}" value="">
                      @endif
                      <div class="btn-group btn-group-sm status-btn-group" role="group" data-student-id="{{ $student->id }}" data-can-unmark="{{ ($canUnmark ?? false) ? '1' : '0' }}">
                        <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="present" id="present_{{ $student->id }}" {{ $status === 'present' ? 'checked' : '' }}>
                        <label class="btn btn-outline-success status-label" for="present_{{ $student->id }}" data-value="present">Present</label>

                        <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="absent" id="absent_{{ $student->id }}" {{ $status === 'absent' ? 'checked' : '' }}>
                        <label class="btn btn-outline-danger status-label" for="absent_{{ $student->id }}" data-value="absent">Absent</label>

                        <input type="radio" class="btn-check mark-radio" name="status_{{ $student->id }}" value="late" id="late_{{ $student->id }}" {{ $status === 'late' ? 'checked' : '' }}>
                        <label class="btn btn-outline-warning status-label" for="late_{{ $student->id }}" data-value="late">Late</label>
                      </div>
                      @if($canUnmark ?? false)
                        <small class="text-muted d-block mt-1">Click same button again to unmark</small>
                      @endif
                    </td>
                    <td>
                      <select name="reason_code_{{ $student->id }}" class="form-select form-select-sm reason-code-select" {{ $status === 'present' ? 'disabled' : '' }}>
                        <option value="">Select Preset Reason</option>
                        @foreach($reasonCodes as $code)
                          <option value="{{ $code->id }}" @selected($reasonCodeId == $code->id)>{{ $code->name }}</option>
                        @endforeach
                      </select>
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
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="small text-muted">
            Total Students: <strong>{{ $students->count() }}</strong>
          </div>
          <button type="button" id="btnOpenSummary" class="btn btn-settings-primary">
            <i class="bi bi-check-circle"></i> Submit Attendance
          </button>
        </div>
      </div>
    </form>
    @else
      <div class="alert alert-soft border-0">
        <i class="bi bi-info-circle"></i> No students found for this selection. Please select a class and date.
      </div>
    @endif
  </div>
</div>

<div class="modal fade summary-modal" id="summaryModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable summary-modal-dialog">
    <div class="modal-content settings-card mb-0">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Attendance Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3"><strong>Date:</strong> {{ $selectedDate }}</p>
        <div class="row g-3 mb-3 stat-grid">
          <div class="col-md-3">
            <div class="settings-card stat-card border-start border-4 border-success h-100">
              <div class="card-body text-center">
                <h4 class="mb-0 text-success" id="sumPresent">0</h4>
                <small class="text-muted">Present</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="settings-card stat-card border-start border-4 border-danger h-100">
              <div class="card-body text-center">
                <h4 class="mb-0 text-danger" id="sumAbsent">0</h4>
                <small class="text-muted">Absent</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="settings-card stat-card border-start border-4 border-warning h-100">
              <div class="card-body text-center">
                <h4 class="mb-0 text-warning" id="sumLate">0</h4>
                <small class="text-muted">Late</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="settings-card stat-card border-start border-4 border-secondary h-100">
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
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btnConfirmSubmit" class="btn btn-primary">Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .summary-modal .modal-content {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
  }

  .summary-modal .modal-header,
  .summary-modal .modal-footer {
    border-color: var(--bs-border-color);
  }

  .summary-modal .stat-card {
    background-color: var(--bs-body-bg);
    border-color: var(--bs-border-color);
    box-shadow: 0 .125rem .25rem rgba(0, 0, 0, 0.06);
  }

  [data-bs-theme="dark"] .summary-modal .stat-card {
    box-shadow: none;
  }

  .summary-modal #absentList {
    max-height: 200px;
    overflow-y: auto;
    padding-left: 1rem;
  }

  .summary-modal .summary-modal-dialog {
    margin: 1.25rem auto;
    align-items: flex-start;
  }

  /* Keep modal clear of sticky header */
  @media (min-width: 576px) {
    .summary-modal .summary-modal-dialog {
      margin-top: 5rem;
    }
  }

  @media (max-width: 576px) {
    .summary-modal .summary-modal-dialog {
      margin: 4rem auto .75rem;
    }
    .summary-modal .stat-card .card-body {
      padding: .75rem;
    }
  }
</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Admin unmark: click same status button again to unmark (remove record)
  document.querySelectorAll('.status-btn-group[data-can-unmark="1"]').forEach(function(group) {
    const studentId = group.getAttribute('data-student-id');
    const unmarkInput = document.querySelector(`.unmark-input[data-student-id="${studentId}"]`);
    if (!unmarkInput) return;
    group.querySelectorAll('.status-label').forEach(function(label) {
      label.addEventListener('click', function(e) {
        const value = this.getAttribute('data-value');
        const radio = document.getElementById(value + '_' + studentId);
        if (!radio) return;
        if (radio.checked) {
          e.preventDefault();
          unmarkInput.value = '1';
          radio.checked = false;
          const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
          row.querySelectorAll('.mark-radio').forEach(function(r) { r.checked = false; });
          row.querySelectorAll('.reason-code-select, textarea[name^="excuse"]').forEach(function(f) {
            f.disabled = false;
            if (f.tagName === 'TEXTAREA') f.value = '';
          });
        }
      });
    });
  });

  // Enable/disable fields based on status
  document.querySelectorAll('.mark-radio').forEach(r => {
    r.addEventListener('change', function() {
      const studentId = this.name.split('_')[1];
      const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
      const isPresent = this.value === 'present';
      const unmarkInput = row.querySelector('.unmark-input[data-student-id="' + studentId + '"]');
      if (unmarkInput) unmarkInput.value = '';
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
