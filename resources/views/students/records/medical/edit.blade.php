@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.medical-records.index', $student) }}">Medical Records</a></li>
      <li class="breadcrumb-item active">Edit Record</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Edit Medical Record - {{ $student->full_name }}</h1>
    <a href="{{ route('students.medical-records.show', [$student, $medicalRecord]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.medical-records.update', [$student, $medicalRecord]) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="card">
      <div class="card-header">Medical Record Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Record Type <span class="text-danger">*</span></label>
            <select name="record_type" class="form-select" required>
              <option value="vaccination" @selected(old('record_type', $medicalRecord->record_type)=='vaccination')>Vaccination</option>
              <option value="checkup" @selected(old('record_type', $medicalRecord->record_type)=='checkup')>Checkup</option>
              <option value="medication" @selected(old('record_type', $medicalRecord->record_type)=='medication')>Medication</option>
              <option value="incident" @selected(old('record_type', $medicalRecord->record_type)=='incident')>Incident</option>
              <option value="certificate" @selected(old('record_type', $medicalRecord->record_type)=='certificate')>Certificate</option>
              <option value="other" @selected(old('record_type', $medicalRecord->record_type)=='other')>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Record Date <span class="text-danger">*</span></label>
            <input type="date" name="record_date" class="form-control" value="{{ old('record_date', $medicalRecord->record_date->toDateString()) }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $medicalRecord->title) }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $medicalRecord->description) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Doctor Name</label>
            <input type="text" name="doctor_name" class="form-control" value="{{ old('doctor_name', $medicalRecord->doctor_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Clinic/Hospital</label>
            <input type="text" name="clinic_hospital" class="form-control" value="{{ old('clinic_hospital', $medicalRecord->clinic_hospital) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Vaccination Name</label>
            <input type="text" name="vaccination_name" class="form-control" value="{{ old('vaccination_name', $medicalRecord->vaccination_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Vaccination Date</label>
            <input type="date" name="vaccination_date" class="form-control" value="{{ old('vaccination_date', $medicalRecord->vaccination_date?->toDateString()) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Next Due Date</label>
            <input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date', $medicalRecord->next_due_date?->toDateString()) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Medication Name</label>
            <input type="text" name="medication_name" class="form-control" value="{{ old('medication_name', $medicalRecord->medication_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Dosage</label>
            <input type="text" name="medication_dosage" class="form-control" value="{{ old('medication_dosage', $medicalRecord->medication_dosage) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Medication Start Date</label>
            <input type="date" name="medication_start_date" class="form-control" value="{{ old('medication_start_date', $medicalRecord->medication_start_date?->toDateString()) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Medication End Date</label>
            <input type="date" name="medication_end_date" class="form-control" value="{{ old('medication_end_date', $medicalRecord->medication_end_date?->toDateString()) }}">
          </div>
          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $medicalRecord->notes) }}</textarea>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.medical-records.show', [$student, $medicalRecord]) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Record</button>
      </div>
    </div>
  </form>
</div>
@endsection

