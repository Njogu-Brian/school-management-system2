@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Details' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">{{ $student->first_name }} {{ $student->last_name }}</h1>
        <p class="text-muted mb-0">Admission #{{ $student->admission_number }}</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        @if(!$student->is_alumni && $student->classroom_id)
          <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#demoteStudentModal">
            <i class="bi bi-arrow-down-circle"></i> Demote
          </button>
        @endif
        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-settings-primary"><i class="bi bi-pencil-square"></i> Edit</a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <div class="avatar-120 flex-shrink-0 overflow-hidden rounded-circle">
            <img
              src="{{ $student->photo_url }}"
              alt="{{ $student->first_name }} {{ $student->last_name }}"
              class="avatar-120"
              onerror="this.onerror=null;this.src='{{ asset('images/avatar-student.png') }}'">
          </div>
          <div class="d-flex flex-column gap-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-light text-dark fw-semibold px-3 py-2">Admission #{{ $student->admission_number }}</span>
              <span class="pill-badge pill-{{ $student->archive ? 'danger' : 'success' }}">
                <i class="bi {{ $student->archive ? 'bi-archive' : 'bi-check2-circle' }} me-1"></i>{{ $student->archive ? 'Archived' : 'Active' }}
              </span>
              @if($student->status)
                <span class="pill-badge pill-{{ $student->archive ? 'danger' : 'info' }} text-capitalize">{{ $student->archive ? 'Inactive' : $student->status }}</span>
              @endif
            </div>
            <h2 class="mb-0">{{ $student->first_name }} {{ $student->last_name }}</h2>
            <div class="text-muted d-flex gap-3 flex-wrap">
              <span><i class="bi bi-mortarboard me-1"></i>{{ $student->classroom->name ?? '—' }}</span>
              <span><i class="bi bi-diagram-3 me-1"></i>{{ $student->stream->name ?? '—' }}</span>
              <span><i class="bi bi-calendar-event me-1"></i>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('M d, Y') : 'Admission date —' }}</span>
            </div>
          </div>
        </div>
        <div class="d-flex flex-column gap-2">
          <div class="mini-stat">
            <i class="bi bi-people-fill text-primary"></i>
            <div>
              <div class="small text-muted">Category</div>
              <div class="fw-semibold">{{ $student->category->name ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-4">
        <div class="settings-card h-100">
          <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">Profile Snapshot</span>
              <span class="badge bg-light text-dark">ID {{ $student->id }}</span>
            </div>
          </div>
          <div class="card-body vstack gap-3">
            <div class="d-flex flex-column gap-2">
              <div class="text-muted small">Gender</div>
              <div class="fw-semibold">{{ $student->gender ? ucfirst($student->gender) : '—' }}</div>
            </div>
            <div class="d-flex flex-column gap-2">
              <div class="text-muted small">Date of Birth</div>
              <div class="fw-semibold">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->toFormattedDateString() : '—' }}</div>
            </div>
        <div class="d-flex flex-column gap-2">
          <div class="text-muted small">Marital Status (Parents)</div>
          @php $marital = optional($student->parent)->marital_status; @endphp
          <div class="fw-semibold text-capitalize">{{ $marital ? str_replace('_',' ', $marital) : '—' }}</div>
        </div>
            <div class="d-flex flex-column gap-2">
              <div class="text-muted small">NEMIS</div>
              <div class="fw-semibold">{{ $student->nemis_number ?? '—' }}</div>
            </div>
            <div class="d-flex flex-column gap-2">
              <div class="text-muted small">KNEC Assessment</div>
              <div class="fw-semibold">{{ $student->knec_assessment_number ?? '—' }}</div>
            </div>
            @if($student->allergies)
            <div class="d-flex flex-column gap-2">
              <div class="text-muted small">Allergies</div>
              <div class="fw-semibold">{{ $student->allergies }}</div>
            </div>
            @endif
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Documents</span>
            <a class="btn btn-sm btn-ghost-strong" href="{{ route('students.edit', $student->id) }}"><i class="bi bi-upload"></i> Manage</a>
          </div>
          <div class="card-body small vstack gap-2">
            <div class="d-flex justify-content-between">
              <span class="fw-semibold">Passport Photo</span>
              <span>{!! $student->photo_path ? '<a target="_blank" href="'.asset('storage/'.$student->photo_path).'">View</a>' : '—' !!}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="fw-semibold">Birth Certificate</span>
              <span>{!! $student->birth_certificate_path ? '<a target="_blank" href="'.asset('storage/'.$student->birth_certificate_path).'">View</a>' : '—' !!}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">Student Information</span>
            @if($student->family && $student->family->updateLink)
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark">Profile Update Link</span>
                <button type="button" class="btn btn-sm btn-ghost-strong" onclick="navigator.clipboard.writeText('{{ route('family-update.form', $student->family->updateLink->token) }}'); this.innerText='Copied'; setTimeout(()=>this.innerText='Copy Link',1500);">Copy Link</button>
              </div>
            @endif
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6"><div class="text-muted small">Admission No.</div><div class="fw-semibold">{{ $student->admission_number }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Gender</div><div class="fw-semibold">{{ $student->gender ? ucfirst($student->gender) : '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Date of Birth</div><div class="fw-semibold">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->toFormattedDateString() : '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Admission Date</div><div class="fw-semibold">{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('M d, Y') : '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Class</div><div class="fw-semibold">{{ $student->classroom->name ?? '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Stream</div><div class="fw-semibold">{{ $student->stream->name ?? '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Category</div><div class="fw-semibold">{{ $student->category->name ?? '—' }}</div></div>
              <div class="col-md-6"><div class="text-muted small">Status</div><div class="fw-semibold"><span class="pill-badge pill-{{ $student->archive ? 'danger' : ($student->status === 'active' ? 'success' : 'secondary') }}">{{ $student->archive ? 'Inactive' : ucfirst($student->status ?? '—') }}</span></div></div>
            </div>
          </div>
        </div>

        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">Parent / Guardian</span>
            @if(!$student->parent)
              <span class="badge bg-warning text-dark">Missing</span>
            @endif
          </div>
          <div class="card-body">
            @if($student->parent)
              @php $p = $student->parent; @endphp
              <div class="row g-3">
                {{-- Father Section --}}
                <div class="col-12"><h6 class="fw-bold text-uppercase text-muted small mb-2">Father</h6></div>
                <div class="col-md-6"><div class="text-muted small">Name</div><div class="fw-semibold">{{ $p->father_name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">ID Number</div><div class="fw-semibold">{{ $p->father_id_number ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Phone</div><div class="fw-semibold">{{ $p->father_phone ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">WhatsApp</div><div class="fw-semibold">{{ $p->father_whatsapp ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $p->father_email ?? '—' }}</div></div>

                {{-- Mother Section --}}
                <div class="col-12 mt-3"><h6 class="fw-bold text-uppercase text-muted small mb-2">Mother</h6></div>
                <div class="col-md-6"><div class="text-muted small">Name</div><div class="fw-semibold">{{ $p->mother_name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">ID Number</div><div class="fw-semibold">{{ $p->mother_id_number ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Phone</div><div class="fw-semibold">{{ $p->mother_phone ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">WhatsApp</div><div class="fw-semibold">{{ $p->mother_whatsapp ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $p->mother_email ?? '—' }}</div></div>

                {{-- Guardian Section --}}
                @if($p->guardian_name)
                <div class="col-12 mt-3"><h6 class="fw-bold text-uppercase text-muted small mb-2">Guardian</h6></div>
                <div class="col-md-6"><div class="text-muted small">Name</div><div class="fw-semibold">{{ $p->guardian_name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Relationship</div><div class="fw-semibold">{{ $p->guardian_relationship ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Phone</div><div class="fw-semibold">{{ $p->guardian_phone ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">WhatsApp</div><div class="fw-semibold">{{ $p->guardian_whatsapp ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $p->guardian_email ?? '—' }}</div></div>
                @endif
              </div>
            @else
              <div class="text-muted">No parent/guardian information available.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    @if($student->archived_reason || $student->archived_notes)
    <div class="settings-card mb-3">
      <div class="card-header">
        <span class="fw-bold">Archive Details</span>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <div class="text-muted small">Reason</div>
            <div class="fw-semibold">{{ $student->archived_reason ?? '—' }}</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Archived At</div>
            <div class="fw-semibold">{{ $student->archived_at ? $student->archived_at->format('Y-m-d H:i') : '—' }}</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Archived By</div>
            <div class="fw-semibold">{{ optional($student->archived_by ? \App\Models\User::find($student->archived_by) : null)->name ?? '—' }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted small">Details</div>
            <div class="fw-semibold">{{ $student->archived_notes ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <div class="row g-3 mt-2">
      <div class="col-12">
        <div class="settings-card mb-4">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div>
                <h5 class="mb-1">Records & History</h5>
                <p class="text-muted mb-0">Recent items with quick access to full lists.</p>
              </div>
            </div>
            <ul class="nav settings-tabs mb-3" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#medical" type="button">Medical Records</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#disciplinary" type="button">Disciplinary</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activities" type="button">Activities</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#academic" type="button">Academic History</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#finance" type="button">Financial</button>
              </li>
            </ul>
            <div class="tab-content">
          <div class="tab-pane fade show active" id="medical" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Medical Records</h6>
              <a href="{{ route('students.medical-records.index', $student) }}" class="btn btn-sm btn-ghost-strong">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            @php $medicalRecords = $student->medicalRecords()->latest('record_date')->limit(5)->get(); @endphp
            @if($medicalRecords->count() > 0)
              <div class="list-group">
                @foreach($medicalRecords as $record)
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <h6 class="mb-1">{{ $record->title }}</h6>
                        <p class="mb-1 text-muted small">{{ $record->record_date->format('M d, Y') }} · <span class="pill-badge pill-info">{{ ucfirst($record->record_type) }}</span></p>
                      </div>
                      <a href="{{ route('students.medical-records.show', [$student, $record]) }}" class="btn btn-sm btn-ghost-strong">View</a>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted mb-0">No medical records yet. <a href="{{ route('students.medical-records.create', $student) }}">Add one</a></p>
            @endif
          </div>

          <div class="tab-pane fade" id="disciplinary" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Disciplinary Records</h6>
              <a href="{{ route('students.disciplinary-records.index', $student) }}" class="btn btn-sm btn-ghost-strong">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            @php $disciplinaryRecords = $student->disciplinaryRecords()->latest('incident_date')->limit(5)->get(); @endphp
            @if($disciplinaryRecords->count() > 0)
              <div class="list-group">
                @foreach($disciplinaryRecords as $record)
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <h6 class="mb-1">{{ $record->incident_type }}</h6>
                        <p class="mb-1 text-muted small">{{ $record->incident_date->format('M d, Y') }} · <span class="pill-badge pill-{{ $record->severity === 'major' || $record->severity === 'severe' ? 'danger' : 'warning' }}">{{ ucfirst($record->severity) }}</span></p>
                      </div>
                      <a href="{{ route('students.disciplinary-records.show', [$student, $record]) }}" class="btn btn-sm btn-ghost-strong">View</a>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted mb-0">No disciplinary records yet. <a href="{{ route('students.disciplinary-records.create', $student) }}">Add one</a></p>
            @endif
          </div>

          <div class="tab-pane fade" id="activities" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Extracurricular Activities</h6>
              <a href="{{ route('students.activities.index', $student) }}" class="btn btn-sm btn-ghost-strong">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            @php $activities = $student->extracurricularActivities()->latest('start_date')->limit(5)->get(); @endphp
            @if($activities->count() > 0)
              <div class="list-group">
                @foreach($activities as $activity)
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <h6 class="mb-1">{{ $activity->activity_name }}</h6>
                        <p class="mb-1 text-muted small">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }} @if($activity->position_role) · {{ $activity->position_role }} @endif</p>
                      </div>
                      <a href="{{ route('students.activities.show', [$student, $activity]) }}" class="btn btn-sm btn-ghost-strong">View</a>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted mb-0">No activities yet. <a href="{{ route('students.activities.create', $student) }}">Add one</a></p>
            @endif
          </div>

          <div class="tab-pane fade" id="academic" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Academic History</h6>
              <a href="{{ route('students.academic-history.index', $student) }}" class="btn btn-sm btn-ghost-strong">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            @php $academicHistory = $student->academicHistory()->latest('enrollment_date')->limit(5)->get(); @endphp
            @if($academicHistory->count() > 0)
              <div class="list-group">
                @foreach($academicHistory as $entry)
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <h6 class="mb-1">{{ $entry->classroom->name ?? '—' }} @if($entry->stream) - {{ $entry->stream->name }} @endif</h6>
                        <p class="mb-1 text-muted small">{{ $entry->enrollment_date->format('M Y') }} @if($entry->is_current) · <span class="pill-badge pill-success">Current</span> @endif</p>
                      </div>
                      <a href="{{ route('students.academic-history.show', [$student, $entry]) }}" class="btn btn-sm btn-ghost-strong">View</a>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted mb-0">No academic history yet. <a href="{{ route('students.academic-history.create', $student) }}">Add one</a></p>
            @endif
          </div>
            <div class="tab-pane fade" id="finance" role="tabpanel">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Financial</h6>
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-ghost-strong" href="{{ url('/finance/invoices') }}">Invoices</a>
                  <a class="btn btn-ghost-strong" href="{{ url('/finance/payments') }}">Payments</a>
                </div>
              </div>
              <p class="text-muted small mb-2">Finance records remain available for archived students. You can collect fees and view invoices/payments in Finance.</p>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Demote Student Modal --}}
@if(!$student->is_alumni && $student->classroom_id)
<div class="modal fade" id="demoteStudentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('academics.promotions.demote', $student) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Demote Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> This will move <strong>{{ $student->full_name }}</strong> to a different class. This action will be recorded in the academic history.
          </div>
          
          <div class="mb-3">
            <label class="form-label">Select Class <span class="text-danger">*</span></label>
            <select name="classroom_id" class="form-select" required id="demoteClassroomSelect">
              <option value="">-- Select Class --</option>
              @foreach(\App\Models\Academics\Classroom::orderBy('name')->get() as $classroom)
                <option value="{{ $classroom->id }}" {{ $classroom->id == $student->classroom_id ? 'disabled' : '' }}>
                  {{ $classroom->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Select Stream (Optional)</label>
            <select name="stream_id" class="form-select" id="demoteStreamSelect">
              <option value="">-- No Stream --</option>
            </select>
            <small class="text-muted">Select a stream after choosing a class</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
              @foreach(\App\Models\AcademicYear::orderBy('year', 'desc')->get() as $year)
                <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>{{ $year->year }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Term <span class="text-danger">*</span></label>
            <select name="term_id" class="form-select" required>
              @foreach(\App\Models\Term::orderBy('name')->get() as $term)
                <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Demotion Date <span class="text-danger">*</span></label>
            <input type="date" name="demotion_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Reason for Demotion <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" required placeholder="Enter the reason for demoting this student..."></textarea>
            <small class="text-muted">This reason will be recorded in the academic history.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-arrow-down-circle"></i> Demote Student
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const classroomSelect = document.getElementById('demoteClassroomSelect');
  const streamSelect = document.getElementById('demoteStreamSelect');

  if (classroomSelect && streamSelect) {
    classroomSelect.addEventListener('change', function() {
      const classroomId = this.value;
      streamSelect.innerHTML = '<option value="">-- No Stream --</option>';

      if (classroomId) {
        fetch(`/students/streams?classroom_id=${classroomId}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data && data.length > 0) {
            data.forEach(stream => {
              const option = document.createElement('option');
              option.value = stream.id;
              option.textContent = stream.name;
              streamSelect.appendChild(option);
            });
          }
        })
        .catch(error => {
          console.error('Error fetching streams:', error);
        });
      }
    });
  }
});
</script>
@endpush
@endif
@endsection
