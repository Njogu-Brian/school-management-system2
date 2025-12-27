@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Online Admissions</div>
        <h1 class="mb-1">Application Details</h1>
        <p class="text-muted mb-0">Review and manage admission application.</p>
      </div>
      <a href="{{ route('online-admissions.index') }}" class="btn btn-ghost-strong">
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
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person"></i> Student Information</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label small text-muted">First Name</label><div class="fw-semibold">{{ $admission->first_name }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Middle Name</label><div>{{ $admission->middle_name ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Last Name</label><div class="fw-semibold">{{ $admission->last_name }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Date of Birth</label><div>{{ $admission->dob?->format('d M Y') ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Gender</label><div>{{ $admission->gender }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Has Allergies?</label><div>{{ $admission->has_allergies ? 'Yes' : 'No' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Allergies Notes</label><div>{{ $admission->allergies_notes ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Fully Immunized?</label><div>{{ $admission->is_fully_immunized ? 'Yes' : 'No' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Preferred Hospital</label><div>{{ $admission->preferred_hospital ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Emergency Contact</label><div>{{ $admission->emergency_contact_phone ?? '—' }}<br>{{ $admission->emergency_contact_name ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Residential Area</label><div>{{ $admission->residential_area ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Preferred Classroom</label><div>{{ $admission->preferred_classroom_id ? optional($classrooms->firstWhere('id', $admission->preferred_classroom_id))->name : '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Marital Status</label><div class="text-capitalize">{{ $admission->marital_status ? str_replace('_',' ', $admission->marital_status) : '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Previous School</label><div>{{ $admission->previous_school ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Transfer Reason</label><div>{{ $admission->transfer_reason ?? '—' }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Application Source</label><div>{{ ucfirst($admission->application_source ?? 'online') }}</div></div>
              <div class="col-md-4"><label class="form-label small text-muted">Application Date</label><div>{{ $admission->application_date?->format('d M Y') ?? '—' }}</div></div>
            </div>
          </div>
        </div>

        <div class="settings-card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-people"></i> Parent/Guardian Information</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <h6 class="text-muted mb-2">Father</h6>
                <div><strong>Name:</strong> {{ $admission->father_name ?? '—' }}</div>
                <div><strong>Phone:</strong> {{ $admission->father_phone_country_code }} {{ $admission->father_phone ?? '—' }}</div>
                <div><strong>WhatsApp:</strong> {{ $admission->father_whatsapp ?? '—' }}</div>
                <div><strong>Email:</strong> {{ $admission->father_email ?? '—' }}</div>
                <div><strong>ID Number:</strong> {{ $admission->father_id_number ?? '—' }}</div>
                <div><strong>ID Document:</strong> @if($admission->father_id_document)<a href="{{ Storage::url($admission->father_id_document) }}" target="_blank">View</a>@else — @endif</div>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted mb-2">Mother</h6>
                <div><strong>Name:</strong> {{ $admission->mother_name ?? '—' }}</div>
                <div><strong>Phone:</strong> {{ $admission->mother_phone_country_code }} {{ $admission->mother_phone ?? '—' }}</div>
                <div><strong>WhatsApp:</strong> {{ $admission->mother_whatsapp ?? '—' }}</div>
                <div><strong>Email:</strong> {{ $admission->mother_email ?? '—' }}</div>
                <div><strong>ID Number:</strong> {{ $admission->mother_id_number ?? '—' }}</div>
                <div><strong>ID Document:</strong> @if($admission->mother_id_document)<a href="{{ Storage::url($admission->mother_id_document) }}" target="_blank">View</a>@else — @endif</div>
              </div>
              <div class="col-md-12">
                <h6 class="text-muted mb-2">Guardian</h6>
                <div class="row">
                  <div class="col-md-4"><strong>Name:</strong> {{ $admission->guardian_name ?? '—' }}</div>
                  <div class="col-md-4"><strong>Phone:</strong> {{ $admission->guardian_phone_country_code }} {{ $admission->guardian_phone ?? '—' }}</div>
                  <div class="col-md-4"><strong>WhatsApp:</strong> {{ $admission->guardian_whatsapp ?? '—' }}</div>
                  <div class="col-md-4"><strong>Email:</strong> {{ $admission->guardian_email ?? '—' }}</div>
                  <div class="col-md-4"><strong>Relationship:</strong> {{ $admission->guardian_relationship ?? '—' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        @if($admission->passport_photo || $admission->birth_certificate)
        <div class="settings-card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-file-earmark"></i> Documents</h5>
          </div>
          <div class="card-body">
            <div class="row g-2">
              @if($admission->passport_photo)
              <div class="col-md-4">
                <a href="{{ route('file.download', ['model'=>'online-admission','id'=>$admission->id,'field'=>'passport_photo']) }}" target="_blank" class="btn btn-ghost-strong w-100">
                  <i class="bi bi-image"></i> Passport Photo
                </a>
              </div>
              @endif
              @if($admission->birth_certificate)
              <div class="col-md-4">
                <a href="{{ route('file.download', ['model'=>'online-admission','id'=>$admission->id,'field'=>'birth_certificate']) }}" target="_blank" class="btn btn-ghost-strong w-100">
                  <i class="bi bi-file-pdf"></i> Birth Certificate
                </a>
              </div>
              @endif
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Application Status</h5>
          </div>
          <div class="card-body">
            @php
              $statusColors = [
                'pending' => 'pill-secondary',
                'under_review' => 'pill-info',
                'accepted' => 'pill-success',
                'rejected' => 'pill-danger',
                'waitlisted' => 'pill-warning'
              ];
              $color = $statusColors[$admission->application_status] ?? 'pill-secondary';
            @endphp
            <div class="text-center mb-3">
              <span class="pill-badge {{ $color }} fs-6 px-3 py-2">
                {{ ucfirst(str_replace('_', ' ', $admission->application_status)) }}
              </span>
              @if($admission->waitlist_position)
                <div class="mt-2">
                  <span class="pill-badge pill-warning">Waitlist Position: #{{ $admission->waitlist_position }}</span>
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

        @if(!$admission->enrolled)
        <div class="settings-card mb-3">
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
              <button type="submit" class="btn btn-settings-primary w-100">
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
                  <div class="mb-3">
                    <label class="form-label">Classroom</label>
                    <select name="classroom_id" class="form-select" required>
                      <option value="">Select Classroom</option>
                      @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected($admission->classroom_id==$classroom->id)>{{ $classroom->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Has Allergies?</label>
                    <select name="has_allergies" class="form-select">
                      <option value="0" @selected(old('has_allergies', $admission->has_allergies)==0)>No</option>
                      <option value="1" @selected(old('has_allergies', $admission->has_allergies)==1)>Yes</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Allergies Notes</label>
                    <textarea name="allergies_notes" class="form-control" rows="2">{{ old('allergies_notes', $admission->allergies_notes) }}</textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Fully Immunized?</label>
                    <select name="is_fully_immunized" class="form-select">
                      <option value="0" @selected(old('is_fully_immunized', $admission->is_fully_immunized)==0)>No</option>
                      <option value="1" @selected(old('is_fully_immunized', $admission->is_fully_immunized)==1)>Yes</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Preferred Hospital / Medical Facility</label>
                    <input type="text" name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital', $admission->preferred_hospital) }}">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $admission->emergency_contact_name) }}">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $admission->emergency_contact_phone) }}" placeholder="+2547XXXXXXXX">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select">
                      <option value="">Select</option>
                      <option value="married" @selected(old('marital_status', $admission->marital_status)=='married')>Married</option>
                      <option value="single_parent" @selected(old('marital_status', $admission->marital_status)=='single_parent')>Single Parent</option>
                      <option value="co_parenting" @selected(old('marital_status', $admission->marital_status)=='co_parenting')>Co-parenting</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Residential Area</label>
                    <input type="text" name="residential_area" class="form-control" value="{{ old('residential_area', $admission->residential_area) }}">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Stream</label>
                    <select name="stream_id" class="form-select">
                      <option value="">Select Stream</option>
                      @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" @selected($admission->stream_id==$stream->id)>{{ $stream->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                      <option value="">Select Category</option>
                      @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Transport Route</label>
                    <select name="route_id" class="form-select">
                      <option value="">—</option>
                      @foreach($routes as $route)
                        <option value="{{ $route->id }}" @selected($admission->route_id==$route->id)>{{ $route->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Trip</label>
                    <select name="trip_id" class="form-select">
                      <option value="">—</option>
                      @foreach($trips as $trip)
                        <option value="{{ $trip->id }}" @selected($admission->trip_id==$trip->id)>{{ $trip->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Drop-off Point</label>
                    <select name="drop_off_point_id" id="admin_drop_off_point_id" class="form-select">
                      <option value="">—</option>
                      @foreach($dropOffPoints as $point)
                        <option value="{{ $point->id }}" @selected($admission->drop_off_point_id==$point->id)>{{ $point->name }}</option>
                      @endforeach
                      <option value="other">Other (specify)</option>
                    </select>
                    <input type="text" class="form-control mt-2" name="drop_off_point_other" id="admin_drop_off_point_other" value="{{ $admission->drop_off_point_other }}" placeholder="Custom drop-off point">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Transport Fee (this term)</label>
                    <input type="number" step="0.01" name="transport_fee_amount" class="form-control" placeholder="0.00">
                    <div class="form-text">Added to the student's invoice on approval.</div>
                  </div>
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

        @if($admission->reviewedBy || $admission->review_notes)
        <div class="settings-card">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Review History</h5>
          </div>
          <div class="card-body">
            @if($admission->reviewedBy)
              <div class="mb-2"><strong>Reviewed by:</strong> {{ $admission->reviewedBy->name }}</div>
            @endif
            @if($admission->review_date)
              <div class="mb-2"><strong>Review date:</strong> {{ $admission->review_date->format('d M Y H:i') }}</div>
            @endif
            @if($admission->review_notes)
              <div><strong>Notes:</strong><p class="mb-0 text-muted">{{ $admission->review_notes }}</p></div>
            @endif
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const select = document.getElementById('admin_drop_off_point_id');
    const other = document.getElementById('admin_drop_off_point_other');
    function sync() {
      if (!select || !other) return;
      const show = select.value === 'other';
      other.style.display = show ? '' : 'none';
      if (!show) other.value = '';
    }
    select?.addEventListener('change', sync);
    sync();
  })();
</script>
@endpush
