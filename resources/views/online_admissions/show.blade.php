@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Application Details</h2>
      <small class="text-muted">Review and manage admission application</small>
    </div>
    <a href="{{ route('online-admissions.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back to List
    </a>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-3">
    {{-- Left Column: Application Info --}}
    <div class="col-md-8">
      {{-- Student Information --}}
      <div class="card mb-3">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-person"></i> Student Information</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small text-muted">First Name</label>
              <div class="fw-semibold">{{ $admission->first_name }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Middle Name</label>
              <div>{{ $admission->middle_name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Last Name</label>
              <div class="fw-semibold">{{ $admission->last_name }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Date of Birth</label>
              <div>{{ $admission->dob?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Gender</label>
              <div>{{ $admission->gender }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">NEMIS Number</label>
              <div>{{ $admission->nemis_number ?? '—' }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">KNEC Assessment</label>
              <div>{{ $admission->knec_assessment_number ?? '—' }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Application Source</label>
              <div>{{ ucfirst($admission->application_source ?? 'online') }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Application Date</label>
              <div>{{ $admission->application_date?->format('d M Y') ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Parent/Guardian Information --}}
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-people"></i> Parent/Guardian Information</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <h6 class="text-muted mb-2">Father</h6>
              <div><strong>Name:</strong> {{ $admission->father_name ?? '—' }}</div>
              <div><strong>Phone:</strong> {{ $admission->father_phone ?? '—' }}</div>
              <div><strong>Email:</strong> {{ $admission->father_email ?? '—' }}</div>
              <div><strong>ID Number:</strong> {{ $admission->father_id_number ?? '—' }}</div>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted mb-2">Mother</h6>
              <div><strong>Name:</strong> {{ $admission->mother_name ?? '—' }}</div>
              <div><strong>Phone:</strong> {{ $admission->mother_phone ?? '—' }}</div>
              <div><strong>Email:</strong> {{ $admission->mother_email ?? '—' }}</div>
              <div><strong>ID Number:</strong> {{ $admission->mother_id_number ?? '—' }}</div>
            </div>
            <div class="col-md-12">
              <h6 class="text-muted mb-2">Guardian</h6>
              <div class="row">
                <div class="col-md-4"><strong>Name:</strong> {{ $admission->guardian_name ?? '—' }}</div>
                <div class="col-md-4"><strong>Phone:</strong> {{ $admission->guardian_phone ?? '—' }}</div>
                <div class="col-md-4"><strong>Email:</strong> {{ $admission->guardian_email ?? '—' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Documents --}}
      @if($admission->passport_photo || $admission->birth_certificate || $admission->parent_id_card)
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-file-earmark"></i> Documents</h5>
        </div>
        <div class="card-body">
          <div class="row g-2">
            @if($admission->passport_photo)
            <div class="col-md-4">
              <a href="{{ Storage::url($admission->passport_photo) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-image"></i> Passport Photo
              </a>
            </div>
            @endif
            @if($admission->birth_certificate)
            <div class="col-md-4">
              <a href="{{ Storage::url($admission->birth_certificate) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-file-pdf"></i> Birth Certificate
              </a>
            </div>
            @endif
            @if($admission->parent_id_card)
            <div class="col-md-4">
              <a href="{{ Storage::url($admission->parent_id_card) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-card-text"></i> Parent ID Card
              </a>
            </div>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>

    {{-- Right Column: Actions & Review --}}
    <div class="col-md-4">
      {{-- Application Status --}}
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-info-circle"></i> Application Status</h5>
        </div>
        <div class="card-body">
          @php
            $statusColors = [
              'pending' => 'secondary',
              'under_review' => 'info',
              'accepted' => 'success',
              'rejected' => 'danger',
              'waitlisted' => 'warning'
            ];
            $color = $statusColors[$admission->application_status] ?? 'secondary';
          @endphp
          <div class="text-center mb-3">
            <span class="badge bg-{{ $color }} fs-6 px-3 py-2">
              {{ ucfirst(str_replace('_', ' ', $admission->application_status)) }}
            </span>
            @if($admission->waitlist_position)
              <div class="mt-2">
                <span class="badge bg-warning">Waitlist Position: #{{ $admission->waitlist_position }}</span>
              </div>
            @endif
          </div>
          @if($admission->enrolled)
            <div class="alert alert-success mb-0">
              <i class="bi bi-check-circle"></i> Student has been enrolled
            </div>
          @endif
        </div>
      </div>

      {{-- Review & Actions --}}
      @if(!$admission->enrolled)
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-gear"></i> Review & Actions</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('online-admissions.update-status', $admission) }}" method="POST" class="mb-3">
            @csrf
            @method('PUT')
            <div class="mb-3">
              <label class="form-label">Update Status</label>
              <select name="application_status" class="form-select" required>
                <option value="pending" @selected($admission->application_status=='pending')>Pending</option>
                <option value="under_review" @selected($admission->application_status=='under_review')>Under Review</option>
                <option value="accepted" @selected($admission->application_status=='accepted')>Accepted</option>
                <option value="rejected" @selected($admission->application_status=='rejected')>Rejected</option>
                <option value="waitlisted" @selected($admission->application_status=='waitlisted')>Waitlisted</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Assign Classroom</label>
              <select name="classroom_id" class="form-select">
                <option value="">Select Classroom</option>
                @foreach($classrooms as $classroom)
                  <option value="{{ $classroom->id }}" @selected($admission->classroom_id==$classroom->id)>{{ $classroom->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Assign Stream</label>
              <select name="stream_id" class="form-select">
                <option value="">Select Stream</option>
                @foreach($streams as $stream)
                  <option value="{{ $stream->id }}" @selected($admission->stream_id==$stream->id)>{{ $stream->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Review Notes</label>
              <textarea name="review_notes" class="form-control" rows="3" placeholder="Add review notes...">{{ old('review_notes', $admission->review_notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Update Status
            </button>
          </form>

          <hr>

          <div class="d-grid gap-2">
            @if($admission->application_status === 'waitlisted')
              <form action="{{ route('online-admissions.transfer', $admission) }}" method="POST" onsubmit="return confirm('Transfer from waiting list and enroll?')">
                @csrf
                <button type="submit" class="btn btn-success w-100">
                  <i class="bi bi-arrow-up-circle"></i> Transfer from Waitlist
                </button>
              </form>
            @else
              <form action="{{ route('online-admissions.approve', $admission) }}" method="POST" onsubmit="return confirm('Approve and enroll this student?')">
                @csrf
                <button type="submit" class="btn btn-success w-100">
                  <i class="bi bi-check-circle"></i> Approve & Enroll
                </button>
              </form>
            @endif
            <form action="{{ route('online-admissions.waitlist', $admission) }}" method="POST" onsubmit="return confirm('Add to waiting list?')">
              @csrf
              <button type="submit" class="btn btn-warning w-100">
                <i class="bi bi-list-ol"></i> Add to Waitlist
              </button>
            </form>
            <form action="{{ route('online-admissions.reject', $admission) }}" method="POST" onsubmit="return confirm('Reject this application?')">
              @csrf
              <button type="submit" class="btn btn-danger w-100">
                <i class="bi bi-x-circle"></i> Reject
              </button>
            </form>
          </div>
        </div>
      </div>
      @endif

      {{-- Review History --}}
      @if($admission->reviewedBy || $admission->review_notes)
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-clock-history"></i> Review History</h5>
        </div>
        <div class="card-body">
          @if($admission->reviewedBy)
            <div class="mb-2">
              <strong>Reviewed by:</strong> {{ $admission->reviewedBy->name }}
            </div>
          @endif
          @if($admission->review_date)
            <div class="mb-2">
              <strong>Review date:</strong> {{ $admission->review_date->format('d M Y H:i') }}
            </div>
          @endif
          @if($admission->review_notes)
            <div>
              <strong>Notes:</strong>
              <p class="mb-0 text-muted">{{ $admission->review_notes }}</p>
            </div>
          @endif
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

