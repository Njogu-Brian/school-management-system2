@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.disciplinary-records.index', $student) }}">Disciplinary Records</a></li>
      <li class="breadcrumb-item active">Add Record</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Add Disciplinary Record - {{ $student->full_name }}</h1>
    <a href="{{ route('students.disciplinary-records.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.disciplinary-records.store', $student) }}" method="POST">
    @csrf
    <div class="card">
      <div class="card-header">Incident Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Incident Date <span class="text-danger">*</span></label>
            <input type="date" name="incident_date" class="form-control" value="{{ old('incident_date', today()->toDateString()) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Incident Time</label>
            <input type="time" name="incident_time" class="form-control" value="{{ old('incident_time') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Incident Type <span class="text-danger">*</span></label>
            <input type="text" name="incident_type" class="form-control" value="{{ old('incident_type') }}" placeholder="e.g., Misconduct, Bullying, Theft" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Severity <span class="text-danger">*</span></label>
            <select name="severity" class="form-select" required>
              <option value="minor" @selected(old('severity')=='minor')>Minor</option>
              <option value="moderate" @selected(old('severity')=='moderate')>Moderate</option>
              <option value="major" @selected(old('severity')=='major')>Major</option>
              <option value="severe" @selected(old('severity')=='severe')>Severe</option>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
          </div>
          <div class="col-md-12">
            <label class="form-label">Witnesses</label>
            <textarea name="witnesses" class="form-control" rows="2" placeholder="List any witnesses">{{ old('witnesses') }}</textarea>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Action Taken</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Action Type</label>
            <select name="action_taken" class="form-select">
              <option value="">Select Action</option>
              <option value="warning" @selected(old('action_taken')=='warning')>Warning</option>
              <option value="verbal_warning" @selected(old('action_taken')=='verbal_warning')>Verbal Warning</option>
              <option value="written_warning" @selected(old('action_taken')=='written_warning')>Written Warning</option>
              <option value="detention" @selected(old('action_taken')=='detention')>Detention</option>
              <option value="suspension" @selected(old('action_taken')=='suspension')>Suspension</option>
              <option value="expulsion" @selected(old('action_taken')=='expulsion')>Expulsion</option>
              <option value="parent_meeting" @selected(old('action_taken')=='parent_meeting')>Parent Meeting</option>
              <option value="counseling" @selected(old('action_taken')=='counseling')>Counseling</option>
              <option value="other" @selected(old('action_taken')=='other')>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Action Date</label>
            <input type="date" name="action_date" class="form-control" value="{{ old('action_date') }}">
          </div>
          <div class="col-md-12">
            <label class="form-label">Action Details</label>
            <textarea name="action_details" class="form-control" rows="3">{{ old('action_details') }}</textarea>
          </div>
          <div class="col-md-12">
            <label class="form-label">Improvement Plan</label>
            <textarea name="improvement_plan" class="form-control" rows="3" placeholder="Steps for improvement">{{ old('improvement_plan') }}</textarea>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Parent Notification</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="parent_notified" value="1" id="parent_notified" @checked(old('parent_notified'))>
              <label class="form-check-label" for="parent_notified">Parent Notified</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Notification Date</label>
            <input type="date" name="parent_notification_date" class="form-control" value="{{ old('parent_notification_date') }}">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Follow-up</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Follow-up Date</label>
            <input type="date" name="follow_up_date" class="form-control" value="{{ old('follow_up_date') }}">
          </div>
          <div class="col-md-6">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="resolved" value="1" id="resolved" @checked(old('resolved'))>
              <label class="form-check-label" for="resolved">Resolved</label>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label">Follow-up Notes</label>
            <textarea name="follow_up_notes" class="form-control" rows="3">{{ old('follow_up_notes') }}</textarea>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.disciplinary-records.index', $student) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Record</button>
      </div>
    </div>
  </form>
</div>
@endsection

