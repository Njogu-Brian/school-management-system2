@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.medical-records.index', $student) }}">Medical Records</a></li>
      <li class="breadcrumb-item active">View Record</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">{{ $medicalRecord->title }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.medical-records.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      <a href="{{ route('students.medical-records.edit', [$student, $medicalRecord]) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Record Type</strong><div><span class="badge bg-info">{{ ucfirst($medicalRecord->record_type) }}</span></div></div>
        <div class="col-md-6"><strong>Record Date</strong><div>{{ $medicalRecord->record_date->format('M d, Y') }}</div></div>
        @if($medicalRecord->doctor_name)
        <div class="col-md-6"><strong>Doctor</strong><div>{{ $medicalRecord->doctor_name }}</div></div>
        @endif
        @if($medicalRecord->clinic_hospital)
        <div class="col-md-6"><strong>Clinic/Hospital</strong><div>{{ $medicalRecord->clinic_hospital }}</div></div>
        @endif
        @if($medicalRecord->description)
        <div class="col-md-12"><strong>Description</strong><div>{{ $medicalRecord->description }}</div></div>
        @endif
        @if($medicalRecord->vaccination_name)
        <div class="col-md-6"><strong>Vaccination</strong><div>{{ $medicalRecord->vaccination_name }}</div></div>
        @endif
        @if($medicalRecord->vaccination_date)
        <div class="col-md-6"><strong>Vaccination Date</strong><div>{{ $medicalRecord->vaccination_date->format('M d, Y') }}</div></div>
        @endif
        @if($medicalRecord->next_due_date)
        <div class="col-md-6"><strong>Next Due Date</strong><div>{{ $medicalRecord->next_due_date->format('M d, Y') }}</div></div>
        @endif
        @if($medicalRecord->medication_name)
        <div class="col-md-6"><strong>Medication</strong><div>{{ $medicalRecord->medication_name }}</div></div>
        @endif
        @if($medicalRecord->medication_dosage)
        <div class="col-md-6"><strong>Dosage</strong><div>{{ $medicalRecord->medication_dosage }}</div></div>
        @endif
        @if($medicalRecord->notes)
        <div class="col-md-12"><strong>Notes</strong><div>{{ $medicalRecord->notes }}</div></div>
        @endif
        @if($medicalRecord->createdBy)
        <div class="col-md-6"><strong>Created By</strong><div>{{ $medicalRecord->createdBy->name }}</div></div>
        @endif
        <div class="col-md-6"><strong>Created At</strong><div>{{ $medicalRecord->created_at->format('M d, Y h:i A') }}</div></div>
      </div>
    </div>
  </div>
</div>
@endsection

