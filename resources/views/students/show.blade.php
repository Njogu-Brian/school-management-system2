@extends('layouts.app')

@section('content')
<div class="container">
  @include('students.partials.breadcrumbs', ['trail' => ['Details' => null]])

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">{{ $student->first_name }} {{ $student->last_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
    </div>
  </div>

  @include('students.partials.alerts')

  <div class="row">
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-body text-center">
          <img
            src="{{ $student->photo_url }}"
            alt="{{ $student->first_name }} {{ $student->last_name }}"
            class="avatar-120"
            onerror="this.onerror=null;this.src='{{ asset('images/avatar-student.png') }}'">
          <div class="fw-bold">{{ $student->first_name }} {{ $student->last_name }}</div>
          <div class="text-muted">{{ $student->admission_number }}</div>
          <div class="mt-2">
            @if($student->archive)
              <span class="badge bg-secondary"><i class="bi bi-archive me-1"></i> Archived</span>
            @else
              <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i> Active</span>
            @endif
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Documents</div>
        <div class="card-body small">
          <div class="mb-2"><strong>Passport Photo:</strong> {!! $student->photo_path ? '<a target="_blank" href="'.asset('storage/'.$student->photo_path).'">View</a>' : '—' !!}</div>
          <div class="mb-2"><strong>Birth Certificate:</strong> {!! $student->birth_certificate_path ? '<a target="_blank" href="'.asset('storage/'.$student->birth_certificate_path).'">View</a>' : '—' !!}</div>
          <div class="mb-2"><strong>Parent/Guardian ID:</strong> {!! $student->parent_id_card ? '<a target="_blank" href="'.asset('storage/'.$student->parent_id_card).'">View</a>' : '—' !!}</div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header">Student Information</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><strong>Admission No.</strong><div>{{ $student->admission_number }}</div></div>
            <div class="col-md-6"><strong>Gender</strong><div>{{ $student->gender }}</div></div>
            <div class="col-md-6"><strong>DOB</strong><div>{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->toFormattedDateString() : '—' }}</div></div>
            <div class="col-md-6"><strong>Class</strong><div>{{ $student->classroom->name ?? '—' }}</div></div>
            <div class="col-md-6"><strong>Stream</strong><div>{{ $student->stream->name ?? '—' }}</div></div>
            <div class="col-md-6"><strong>Category</strong><div>{{ $student->category->name ?? '—' }}</div></div>
            <div class="col-md-6"><strong>NEMIS</strong><div>{{ $student->nemis_number ?? '—' }}</div></div>
            <div class="col-md-6"><strong>KNEC Assessment</strong><div>{{ $student->knec_assessment_number ?? '—' }}</div></div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Parent / Guardian</div>
        <div class="card-body">
          @if($student->parent)
            <div class="row g-3">
              <div class="col-md-6"><strong>Father</strong><div>{{ $student->parent->father_name ?? '—' }}</div></div>
              <div class="col-md-6"><strong>Mother</strong><div>{{ $student->parent->mother_name ?? '—' }}</div></div>
              <div class="col-md-6"><strong>Guardian</strong><div>{{ $student->parent->guardian_name ?? '—' }}</div></div>
              <div class="col-md-6"><strong>Phone</strong><div>{{ $student->parent->father_phone ?? $student->parent->mother_phone ?? $student->parent->guardian_phone ?? '—' }}</div></div>
            </div>
          @else
            <div class="text-muted">No parent/guardian information available.</div>
          @endif
        </div>
      </div>

      {{-- Extended Demographics & Medical Info --}}
      @if($student->blood_group || $student->allergies || $student->status)
      <div class="card mb-3">
        <div class="card-header">Additional Information</div>
        <div class="card-body">
          <div class="row g-3">
            @if($student->status)
            <div class="col-md-6"><strong>Status</strong><div><span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span></div></div>
            @endif
            @if($student->blood_group)
            <div class="col-md-6"><strong>Blood Group</strong><div>{{ $student->blood_group }}</div></div>
            @endif
            @if($student->allergies)
            <div class="col-md-12"><strong>Allergies</strong><div>{{ $student->allergies }}</div></div>
            @endif
            @if($student->admission_date)
            <div class="col-md-6"><strong>Admission Date</strong><div>{{ \Carbon\Carbon::parse($student->admission_date)->format('M d, Y') }}</div></div>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>

  {{-- Tabs for Student Records --}}
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
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
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content">
        <div class="tab-pane fade show active" id="medical" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Medical Records</h6>
            <a href="{{ route('students.medical-records.index', $student) }}" class="btn btn-sm btn-primary">View All <i class="bi bi-arrow-right"></i></a>
          </div>
          @php
            $medicalRecords = $student->medicalRecords()->latest('record_date')->limit(5)->get();
          @endphp
          @if($medicalRecords->count() > 0)
            <div class="list-group">
              @foreach($medicalRecords as $record)
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $record->title }}</h6>
                      <p class="mb-1 text-muted small">{{ $record->record_date->format('M d, Y') }} · <span class="badge bg-info">{{ ucfirst($record->record_type) }}</span></p>
                    </div>
                    <a href="{{ route('students.medical-records.show', [$student, $record]) }}" class="btn btn-sm btn-outline-primary">View</a>
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
            <a href="{{ route('students.disciplinary-records.index', $student) }}" class="btn btn-sm btn-primary">View All <i class="bi bi-arrow-right"></i></a>
          </div>
          @php
            $disciplinaryRecords = $student->disciplinaryRecords()->latest('incident_date')->limit(5)->get();
          @endphp
          @if($disciplinaryRecords->count() > 0)
            <div class="list-group">
              @foreach($disciplinaryRecords as $record)
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $record->incident_type }}</h6>
                      <p class="mb-1 text-muted small">{{ $record->incident_date->format('M d, Y') }} · <span class="badge bg-{{ $record->severity === 'major' || $record->severity === 'severe' ? 'danger' : 'warning' }}">{{ ucfirst($record->severity) }}</span></p>
                    </div>
                    <a href="{{ route('students.disciplinary-records.show', [$student, $record]) }}" class="btn btn-sm btn-outline-primary">View</a>
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
            <a href="{{ route('students.activities.index', $student) }}" class="btn btn-sm btn-primary">View All <i class="bi bi-arrow-right"></i></a>
          </div>
          @php
            $activities = $student->extracurricularActivities()->latest('start_date')->limit(5)->get();
          @endphp
          @if($activities->count() > 0)
            <div class="list-group">
              @foreach($activities as $activity)
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $activity->activity_name }}</h6>
                      <p class="mb-1 text-muted small">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }} @if($activity->position_role) · {{ $activity->position_role }} @endif</p>
                    </div>
                    <a href="{{ route('students.activities.show', [$student, $activity]) }}" class="btn btn-sm btn-outline-primary">View</a>
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
            <a href="{{ route('students.academic-history.index', $student) }}" class="btn btn-sm btn-primary">View All <i class="bi bi-arrow-right"></i></a>
          </div>
          @php
            $academicHistory = $student->academicHistory()->latest('enrollment_date')->limit(5)->get();
          @endphp
          @if($academicHistory->count() > 0)
            <div class="list-group">
              @foreach($academicHistory as $entry)
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $entry->classroom->name ?? '—' }} @if($entry->stream) - {{ $entry->stream->name }} @endif</h6>
                      <p class="mb-1 text-muted small">{{ $entry->enrollment_date->format('M Y') }} @if($entry->is_current) · <span class="badge bg-success">Current</span> @endif</p>
                    </div>
                    <a href="{{ route('students.academic-history.show', [$student, $entry]) }}" class="btn btn-sm btn-outline-primary">View</a>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-muted mb-0">No academic history yet. <a href="{{ route('students.academic-history.create', $student) }}">Add one</a></p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
