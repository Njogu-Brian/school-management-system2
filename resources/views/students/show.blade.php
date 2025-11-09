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
    </div>
  </div>
</div>
@endsection
