@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Mark Attendance</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('attendance.mark.form') }}" class="row g-3 mb-3">
        {{-- Class --}}
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class" id="classSelect" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                @foreach($classes as $id => $name)
                    <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Stream --}}
        <div class="col-md-3">
            <label class="form-label">Stream</label>
            <select name="stream" id="streamSelect" class="form-select" {{ $streams->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                <option value="">-- All Streams --</option>
                @foreach($streams as $id => $name)
                    <option value="{{ $id }}" {{ $selectedStream == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date --}}
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit()">
        </div>

        {{-- Search --}}
        <div class="col-md-4">
            <label class="form-label">Search (Name or Admission #)</label>
            <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="e.g. 01523 or Ann" />
        </div>
    </form>

    @if ($students->isNotEmpty())
    <form id="attendanceForm" method="POST" action="{{ route('attendance.mark') }}">
        @csrf
        <input type="hidden" name="class" value="{{ $selectedClass }}">
        <input type="hidden" name="stream" value="{{ $selectedStream }}">
        <input type="hidden" name="date" value="{{ $selectedDate }}">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <button type="button" id="btnMarkAllPresent" class="btn btn-outline-success btn-sm">Mark All Present</button>
            </div>
            <div class="small text-muted">
                ⚠️ Unmarked students: {{ $unmarkedCount }}
            </div>
        </div>

        <table class="table table-bordered table-hover align-middle" id="attendanceTable">
            <thead class="table-light">
                <tr>
                    <th style="width:70px;">No.</th>
                    <th>Admission #</th>
                    <th>Name</th>
                    <th style="width:350px;">Mark</th>
                    <th>Reason (if absent/late)</th>
                </tr>
            </thead>
            <tbody>
                @php $row = 0; @endphp
                @foreach ($students as $student)
                    @php
                        $row++;
                        $att = $attendanceRecords->get($student->id);
                        $status = $att ? $att->status : null; // 'present', 'absent', 'late'
                        $reasonVal = $att ? $att->reason : '';
                    @endphp
                    <tr data-search="{{ strtolower($student->admission_number.' '.$student->first_name.' '.$student->middle_name.' '.$student->last_name) }}">
                        <td>{{ $row }}</td>
                        <td>{{ $student->admission_number }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input mark-radio"
                                           type="radio"
                                           name="status_{{ $student->id }}"
                                           value="present"
                                           {{ $status === 'present' ? 'checked' : '' }}>
                                    <label class="form-check-label">Present</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input mark-radio"
                                           type="radio"
                                           name="status_{{ $student->id }}"
                                           value="absent"
                                           {{ $status === 'absent' ? 'checked' : '' }}>
                                    <label class="form-check-label">Absent</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input mark-radio"
                                           type="radio"
                                           name="status_{{ $student->id }}"
                                           value="late"
                                           {{ $status === 'late' ? 'checked' : '' }}>
                                    <label class="form-check-label">Late</label>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input type="text"
                                   name="reason_{{ $student->id }}"
                                   class="form-control reason-input"
                                   value="{{ $reasonVal }}"
                                   {{ $status === 'present' ? 'disabled' : '' }}>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-end">
            <button type="button" id="btnOpenSummary" class="btn btn-primary">Submit</button>
        </div>
    </form>
    @else
        <p>No students found for this selection.</p>
    @endif
</div>

{{-- ================= Summary Modal ================= --}}
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="summaryLabel">Confirm Attendance Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Date:</strong> {{ $selectedDate }}</p>
        <p class="mb-1"><strong>Total Students:</strong> <span id="sumTotal">0</span></p>
        <p class="mb-1"><strong>Present:</strong> <span id="sumPresent">0</span></p>
        <p class="mb-1"><strong>Absent:</strong> <span id="sumAbsent">0</span></p>
        <p class="mb-3"><strong>Late:</strong> <span id="sumLate">0</span></p>
        <div id="absentListWrap" style="display:none;">
          <strong>Absent Students:</strong>
          <ul id="absentList" class="mb-0"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btnConfirmSubmit" class="btn btn-primary">Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Enable/disable reason fields
    document.querySelectorAll('.mark-radio').forEach(r => {
        r.addEventListener('change', function() {
            const id = this.name.split('_')[1];
            const reason = document.querySelector(`input[name="reason_${id}"]`);
            if (this.value === 'present') {
                reason.disabled = true;
                reason.required = false;
                reason.value = '';
            } else {
                reason.disabled = false;
                reason.required = true;
            }
        });
    });

    // Mark all present
    document.getElementById('btnMarkAllPresent')?.addEventListener('click', function() {
        document.querySelectorAll('.mark-radio[value="present"]').forEach(r => {
            r.checked = true;
            const id = r.name.split('_')[1];
            const reason = document.querySelector(`input[name="reason_${id}"]`);
            reason.disabled = true;
            reason.required = false;
            reason.value = '';
        });
    });

    // Open summary modal
    document.getElementById('btnOpenSummary')?.addEventListener('click', function() {
        const rows = Array.from(document.querySelectorAll('#attendanceTable tbody tr'));
        let total = 0, present = 0, absent = 0, late = 0;
        const absentNames = [];

        rows.forEach(tr => {
            const radios = tr.querySelectorAll('.mark-radio');
            const checked = Array.from(radios).find(r => r.checked);
            if (!checked) return;
            total++;
            if (checked.value === 'present') present++;
            else if (checked.value === 'absent') {
                absent++;
                absentNames.push(tr.children[2].innerText.trim());
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
