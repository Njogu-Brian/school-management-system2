@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">HR / Staff registrations</div>
        <h1 class="mb-1">{{ $registration->full_name }}</h1>
        <p class="text-muted mb-0">Submitted {{ $registration->created_at?->format('d M Y, H:i') }}</p>
      </div>
      <a href="{{ route('staff.registrations.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Application</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4"><div class="small text-muted">First name</div><div class="fw-semibold">{{ $registration->first_name }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Middle name</div><div>{{ $registration->middle_name ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Last name</div><div class="fw-semibold">{{ $registration->last_name }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Gender</div><div>{{ $registration->gender }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Date of birth</div><div>{{ $registration->date_of_birth?->format('d M Y') }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Marital status</div><div>{{ $registration->marital_status ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">National ID</div><div>{{ $registration->id_number }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Phone</div><div>{{ $registration->phone_number }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Alternative phone</div><div>{{ $registration->emergency_contact_phone ?: '—' }}</div></div>
              <div class="col-12"><div class="small text-muted">Personal email</div><div>{{ $registration->personal_email }}</div></div>
              <div class="col-md-4"><div class="small text-muted">KRA PIN</div><div>{{ $registration->kra_pin ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">NSSF</div><div>{{ $registration->nssf ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">SHIF / NHIF</div><div>{{ $registration->nhif ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Bank</div><div>{{ $registration->bank_name ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Branch</div><div>{{ $registration->bank_branch ?: '—' }}</div></div>
              <div class="col-md-4"><div class="small text-muted">Account</div><div>{{ $registration->bank_account ?: '—' }}</div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="settings-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Review</h5>
            <span class="badge bg-{{ $registration->status === 'approved' ? 'success' : ($registration->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($registration->status) }}</span>
          </div>
          <div class="card-body">
            @if ($registration->isPending())
              <form method="POST" action="{{ route('staff.registrations.approve', $registration) }}" class="mb-4">
                @csrf
                <div class="mb-3">
                  <label class="form-label" for="work_email">Work email</label>
                  <input id="work_email" type="email" name="work_email" class="form-control" value="{{ old('work_email', $suggestedEmail) }}" required>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="staff_category_id">Category</label>
                  <select id="staff_category_id" name="staff_category_id" class="form-select">
                    <option value="">Default (Teaching)</option>
                    @foreach ($categories as $cat)
                      <option value="{{ $cat->id }}" @selected(old('staff_category_id')==$cat->id)>{{ $cat->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="job_title_id">Job title</label>
                  <select id="job_title_id" name="job_title_id" class="form-select">
                    <option value="">Default (Teacher)</option>
                    @foreach ($jobTitles as $title)
                      <option value="{{ $title->id }}" @selected(old('job_title_id')==$title->id)>{{ $title->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="department_id">Department</label>
                  <select id="department_id" name="department_id" class="form-select">
                    <option value="">—</option>
                    @foreach ($departments as $dept)
                      <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>{{ $dept->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="spatie_role_id">System role</label>
                  <select id="spatie_role_id" name="spatie_role_id" class="form-select">
                    <option value="">Default (Teacher)</option>
                    @foreach ($roles as $role)
                      <option value="{{ $role->id }}" @selected(old('spatie_role_id')==$role->id)>{{ $role->name }}</option>
                    @endforeach
                  </select>
                </div>
                <button type="submit" class="btn btn-settings-primary w-100">
                  <i class="bi bi-check-circle"></i> Approve and create staff
                </button>
                <p class="small text-muted mt-2 mb-0">Password will be their national ID. Staff ID is assigned automatically.</p>
              </form>

              <form method="POST" action="{{ route('staff.registrations.reject', $registration) }}">
                @csrf
                <label class="form-label" for="rejection_reason">Reject reason</label>
                <textarea id="rejection_reason" name="rejection_reason" class="form-control mb-2" rows="2" required>{{ old('rejection_reason') }}</textarea>
                <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
              </form>
            @else
              <p class="mb-1">Reviewed by {{ $registration->reviewer?->name ?? '—' }}</p>
              <p class="text-muted small">{{ $registration->reviewed_at?->format('d M Y, H:i') }}</p>
              @if ($registration->status === 'approved' && $registration->staff)
                <a href="{{ route('staff.show', $registration->staff_id) }}" class="btn btn-settings-primary">Open staff profile</a>
                <p class="small mt-2 mb-0">{{ $registration->staff->staff_id }} · {{ $registration->staff->work_email }}</p>
              @endif
              @if ($registration->rejection_reason)
                <p class="mb-0"><strong>Reason:</strong> {{ $registration->rejection_reason }}</p>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
