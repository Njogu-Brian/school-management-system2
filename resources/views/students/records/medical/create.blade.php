@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.medical-records.index', $student) }}">Medical Records</a></li>
      <li class="breadcrumb-item active">Add Record</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Add Medical Record - {{ $student->first_name }} {{ $student->last_name }}</h1>
    <a href="{{ route('students.medical-records.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.medical-records.store', $student) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="card">
      <div class="card-header">Medical Record Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Record Type <span class="text-danger">*</span></label>
            <select name="record_type" class="form-select" required>
              <option value="">Select Type</option>
              <option value="vaccination" @selected(old('record_type')=='vaccination')>Vaccination</option>
              <option value="checkup" @selected(old('record_type')=='checkup')>Checkup</option>
              <option value="medication" @selected(old('record_type')=='medication')>Medication</option>
              <option value="incident" @selected(old('record_type')=='incident')>Incident</option>
              <option value="certificate" @selected(old('record_type')=='certificate')>Certificate</option>
              <option value="other" @selected(old('record_type')=='other')>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Record Date <span class="text-danger">*</span></label>
            <input type="date" name="record_date" class="form-control" value="{{ old('record_date', today()->toDateString()) }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Doctor Name</label>
            <input type="text" name="doctor_name" class="form-control" value="{{ old('doctor_name') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Clinic/Hospital</label>
            <input type="text" name="clinic_hospital" class="form-control" value="{{ old('clinic_hospital') }}">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Vaccination Details (if applicable)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Vaccination Name</label>
            <input type="text" name="vaccination_name" class="form-control" value="{{ old('vaccination_name') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Vaccination Date</label>
            <input type="date" name="vaccination_date" class="form-control" value="{{ old('vaccination_date') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Next Due Date</label>
            <input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date') }}">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Medication Details (if applicable)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Medication Name</label>
            <input type="text" name="medication_name" class="form-control" value="{{ old('medication_name') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Dosage</label>
            <input type="text" name="medication_dosage" class="form-control" value="{{ old('medication_dosage') }}" placeholder="e.g., 500mg twice daily">
          </div>
          <div class="col-md-6">
            <label class="form-label">Start Date</label>
            <input type="date" name="medication_start_date" class="form-control" value="{{ old('medication_start_date') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="medication_end_date" class="form-control" value="{{ old('medication_end_date') }}">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Certificate (if applicable)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Certificate Type</label>
            <input type="text" name="certificate_type" class="form-control" value="{{ old('certificate_type') }}" placeholder="e.g., Medical Certificate, Fitness Certificate">
          </div>
          <div class="col-md-6">
            <label class="form-label">Certificate File</label>
            <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
          </div>
        </div>

        <hr class="my-4">

        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Additional Notes</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.medical-records.index', $student) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Record</button>
      </div>
    </div>
  </form>
</div>
@endsection

