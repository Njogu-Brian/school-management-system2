{{-- Student Header --}}
<div class="mb-4">
  <div class="d-flex align-items-center gap-3 mb-3">
    <div class="avatar-120 flex-shrink-0 overflow-hidden rounded-circle">
      <img src="{{ $student->photo_url }}" alt="{{ $student->first_name }} {{ $student->last_name }}" class="avatar-120" onerror="this.onerror=null;this.src='{{ asset('images/avatar-student.png') }}'">
    </div>
    <div>
      <h4 class="mb-1">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</h4>
      <div class="text-muted">
        <span class="me-2">Admission #{{ $student->admission_number }}</span>
        @if($student->is_alumni)
          <span class="pill-badge pill-primary pill-sm">
            <i class="bi bi-mortarboard me-1"></i>Alumni
          </span>
        @elseif($student->archive)
          <span class="pill-badge pill-danger pill-sm">
            <i class="bi bi-archive me-1"></i>Archived
          </span>
        @endif
      </div>
      <div class="text-muted small mt-1">
        <span class="me-3">{{ $student->classroom->name ?? '—' }}</span>
        <span>{{ $student->stream->name ?? '—' }}</span>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('students.show', $student->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
      <i class="bi bi-box-arrow-up-right me-1"></i> Full Profile
    </a>
    @if(Route::has('finance.payments.create'))
      <a href="{{ route('finance.payments.create', ['student_id' => $student->id]) }}" class="btn btn-sm btn-primary" target="_blank">
        <i class="bi bi-cash-stack me-1"></i> Collect Payment
      </a>
    @endif
    @if($student->archive && !$student->is_alumni)
      <form action="{{ route('students.restore', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this student?');">
        @csrf
        <button type="submit" class="btn btn-sm btn-success">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
        </button>
      </form>
    @endif
  </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#info-tab" type="button">Information</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#financial-tab" type="button">Financial</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#attendance-tab" type="button">Attendance</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#academic-tab" type="button">Academic</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#behavior-tab" type="button">Behavior</button>
  </li>
</ul>

<div class="tab-content">
  {{-- Information Tab --}}
  <div class="tab-pane fade show active" id="info-tab" role="tabpanel">
    <div class="row g-3">
      <div class="col-md-6">
        <h6 class="fw-semibold mb-3">Personal Information</h6>
        <div class="mb-2">
          <div class="text-muted small">Gender</div>
          <div class="fw-semibold">{{ $student->gender ?? '—' }}</div>
        </div>
        <div class="mb-2">
          <div class="text-muted small">Date of Birth</div>
          <div class="fw-semibold">{{ $student->dob ? $student->dob->format('M d, Y') : '—' }}</div>
        </div>
        @if($student->is_alumni && $student->alumni_date)
          <div class="mb-2">
            <div class="text-muted small">Alumni Date</div>
            <div class="fw-semibold">{{ $student->alumni_date->format('M d, Y') }}</div>
          </div>
        @endif
        @if($student->archive && $student->archived_at)
          <div class="mb-2">
            <div class="text-muted small">Archived Date</div>
            <div class="fw-semibold">{{ $student->archived_at->format('M d, Y H:i') }}</div>
          </div>
          @if($student->archived_reason)
            <div class="mb-2">
              <div class="text-muted small">Archive Reason</div>
              <div class="fw-semibold">{{ $student->archived_reason }}</div>
            </div>
          @endif
        @endif
      </div>
      @if($student->parent)
        <div class="col-md-6">
          <h6 class="fw-semibold mb-3">Parent/Guardian</h6>
          <div class="mb-2">
            <div class="text-muted small">Name</div>
            <div class="fw-semibold">{{ $student->parent->primary_contact_name ?? $student->parent->father_name ?? $student->parent->mother_name ?? '—' }}</div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Phone</div>
            <div class="fw-semibold">{{ $student->parent->primary_contact_phone ?? $student->parent->father_phone ?? $student->parent->mother_phone ?? '—' }}</div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Email</div>
            <div class="fw-semibold">{{ $student->parent->primary_contact_email ?? $student->parent->father_email ?? $student->parent->mother_email ?? '—' }}</div>
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- Financial Tab --}}
  <div class="tab-pane fade" id="financial-tab" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Financial Summary</h6>
      <div class="d-flex gap-2">
        @if(Route::has('finance.student-statements.show'))
          <a href="{{ route('finance.student-statements.show', $student->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
            <i class="bi bi-file-text me-1"></i> Full Statement
          </a>
        @endif
        @if(Route::has('finance.payments.create'))
          <a href="{{ route('finance.payments.create', ['student_id' => $student->id]) }}" class="btn btn-sm btn-primary" target="_blank">
            <i class="bi bi-cash-stack me-1"></i> Collect Payment
          </a>
        @endif
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted small">Total Outstanding</div>
            <div class="fw-bold fs-4">{{ number_format($totalOutstanding, 2) }} Ksh</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="text-muted small">Invoice Balance</div>
            <div class="fw-bold fs-4">{{ number_format($invoiceBalance, 2) }} Ksh</div>
          </div>
        </div>
      </div>
      @if($balanceBroughtForward > 0)
        <div class="col-md-4">
          <div class="card">
            <div class="card-body">
              <div class="text-muted small">Balance Brought Forward</div>
              <div class="fw-bold fs-4">{{ number_format($balanceBroughtForward, 2) }} Ksh</div>
            </div>
          </div>
        </div>
      @endif
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <h6 class="fw-semibold mb-3">Recent Invoices</h6>
        @if($recentInvoices->count() > 0)
          <div class="list-group" style="max-height: 400px; overflow-y: auto;">
            @foreach($recentInvoices as $invoice)
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">{{ $invoice->invoice_number }}</h6>
                    <p class="mb-1 text-muted small">{{ $invoice->created_at->format('M d, Y') }} · 
                      <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($invoice->status) }}</span>
                    </p>
                    <p class="mb-0 small">Total: {{ number_format($invoice->total, 2) }} Ksh · Balance: {{ number_format($invoice->balance, 2) }} Ksh</p>
                  </div>
                  @if(Route::has('finance.invoices.show'))
                    <a href="{{ route('finance.invoices.show', $invoice->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">View</a>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-muted">No invoices found.</p>
        @endif
      </div>
      <div class="col-md-6">
        <h6 class="fw-semibold mb-3">Recent Payments</h6>
        @if($recentPayments->count() > 0)
          <div class="list-group" style="max-height: 400px; overflow-y: auto;">
            @foreach($recentPayments as $payment)
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">{{ $payment->receipt_number ?? 'Payment #'.$payment->id }}</h6>
                    <p class="mb-1 text-muted small">{{ $payment->payment_date->format('M d, Y') }} · 
                      <span class="badge bg-success">{{ $payment->payment_method }}</span>
                    </p>
                    <p class="mb-0 small">Amount: {{ number_format($payment->amount, 2) }} Ksh</p>
                  </div>
                  @if(Route::has('finance.payments.receipt.view'))
                    <a href="{{ route('finance.payments.receipt.view', $payment->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">View</a>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-muted">No payments found.</p>
        @endif
      </div>
    </div>
  </div>

  {{-- Attendance Tab --}}
  <div class="tab-pane fade" id="attendance-tab" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Attendance Records</h6>
      @if(Route::has('attendance.records'))
        <a href="{{ route('attendance.records', ['student_id' => $student->id, 'mode' => 'student']) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
          <i class="bi bi-calendar-check me-1"></i> Full Report
        </a>
      @endif
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <div class="text-muted small">Total Records</div>
            <div class="fw-bold fs-4">{{ $attendanceStats['total'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <div class="text-muted small">Present</div>
            <div class="fw-bold fs-4 text-success">{{ $attendanceStats['present'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <div class="text-muted small">Absent</div>
            <div class="fw-bold fs-4 text-danger">{{ $attendanceStats['absent'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body text-center">
            <div class="text-muted small">Attendance %</div>
            <div class="fw-bold fs-4">{{ $attendanceStats['percent'] }}%</div>
          </div>
        </div>
      </div>
    </div>

    <h6 class="fw-semibold mb-3">Recent Attendance (Last 30 records)</h6>
    @if($recentAttendance->count() > 0)
      <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-hover">
          <thead class="table-light sticky-top">
            <tr>
              <th>Date</th>
              <th>Status</th>
              <th>Time</th>
              <th>Reason</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentAttendance as $attendance)
              <tr>
                <td>{{ $attendance->date->format('M d, Y') }}</td>
                <td>
                  <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger') }}">
                    {{ ucfirst($attendance->status) }}
                  </span>
                </td>
                <td>{{ $attendance->created_at->format('H:i') }}</td>
                <td>{{ $attendance->notes ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p class="text-muted">No attendance records found.</p>
    @endif
  </div>

  {{-- Academic Tab --}}
  <div class="tab-pane fade" id="academic-tab" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Academic History</h6>
      @if(Route::has('students.academic-history.index'))
        <a href="{{ route('students.academic-history.index', $student->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
          <i class="bi bi-mortarboard me-1"></i> Full History
        </a>
      @endif
    </div>

    @if($academicHistory->count() > 0)
      <div class="list-group" style="max-height: 500px; overflow-y: auto;">
        @foreach($academicHistory as $entry)
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">
                  {{ $entry->classroom->name ?? '—' }}
                  @if($entry->stream) - {{ $entry->stream->name }} @endif
                  @if($entry->academicYear) ({{ $entry->academicYear->year }}) @endif
                </h6>
                <p class="mb-1 text-muted small">
                  Enrollment: {{ $entry->enrollment_date ? $entry->enrollment_date->format('M Y') : '—' }}
                  @if($entry->completion_date) · Completion: {{ $entry->completion_date->format('M Y') }} @endif
                  @if($entry->is_current) · <span class="badge bg-success">Current</span> @endif
                </p>
                @if($entry->final_grade || $entry->class_position)
                  <p class="mb-0 small">
                    @if($entry->final_grade) Final Grade: {{ $entry->final_grade }} @endif
                    @if($entry->class_position) · Position: {{ $entry->class_position }} @endif
                  </p>
                @endif
              </div>
              @if(Route::has('students.academic-history.show'))
                <a href="{{ route('students.academic-history.show', [$student->id, $entry->id]) }}" class="btn btn-sm btn-ghost-strong" target="_blank">View</a>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-muted">No academic history found.</p>
    @endif
  </div>

  {{-- Behavior Tab --}}
  <div class="tab-pane fade" id="behavior-tab" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Disciplinary Records</h6>
      @if(Route::has('students.disciplinary-records.index'))
        <a href="{{ route('students.disciplinary-records.index', $student->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
          <i class="bi bi-shield-exclamation me-1"></i> All Records
        </a>
      @endif
    </div>

    @if($disciplinaryRecords->count() > 0)
      <div class="list-group" style="max-height: 500px; overflow-y: auto;">
        @foreach($disciplinaryRecords as $record)
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">{{ $record->incident_type ?? 'Disciplinary Record' }}</h6>
                <p class="mb-1 text-muted small">
                  {{ $record->incident_date ? $record->incident_date->format('M d, Y') : '—' }}
                  @if($record->severity)
                    · <span class="badge bg-{{ $record->severity === 'major' || $record->severity === 'severe' ? 'danger' : 'warning' }}">
                      {{ ucfirst($record->severity) }}
                    </span>
                  @endif
                </p>
                @if($record->description)
                  <p class="mb-0 small">{{ Str::limit($record->description, 100) }}</p>
                @endif
              </div>
              @if(Route::has('students.disciplinary-records.show'))
                <a href="{{ route('students.disciplinary-records.show', [$student->id, $record->id]) }}" class="btn btn-sm btn-ghost-strong" target="_blank">View</a>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-muted">No disciplinary records found.</p>
    @endif
  </div>
</div>
