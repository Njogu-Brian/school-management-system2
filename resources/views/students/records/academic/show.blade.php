@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.academic-history.index', $student) }}">Academic History</a></li>
      <li class="breadcrumb-item active">View Entry</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Academic History Entry</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.academic-history.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      <a href="{{ route('students.academic-history.edit', [$student, $academicHistory]) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Classroom</strong><div>{{ $academicHistory->classroom->name ?? '—' }}</div></div>
        <div class="col-md-6"><strong>Stream</strong><div>{{ $academicHistory->stream->name ?? '—' }}</div></div>
        <div class="col-md-6"><strong>Enrollment Date</strong><div>{{ $academicHistory->enrollment_date->format('M d, Y') }}</div></div>
        <div class="col-md-6"><strong>Completion Date</strong><div>{{ $academicHistory->completion_date ? $academicHistory->completion_date->format('M d, Y') : '—' }}</div></div>
        @if($academicHistory->promotion_status)
        <div class="col-md-6"><strong>Promotion Status</strong><div><span class="badge bg-info">{{ ucfirst($academicHistory->promotion_status) }}</span></div></div>
        @endif
        @if($academicHistory->final_grade)
        <div class="col-md-6"><strong>Final Grade</strong><div>{{ $academicHistory->final_grade }}</div></div>
        @endif
        @if($academicHistory->class_position)
        <div class="col-md-6"><strong>Class Position</strong><div>{{ $academicHistory->class_position }}</div></div>
        @endif
        @if($academicHistory->stream_position)
        <div class="col-md-6"><strong>Stream Position</strong><div>{{ $academicHistory->stream_position }}</div></div>
        @endif
        <div class="col-md-6"><strong>Current</strong><div>
          @if($academicHistory->is_current)
            <span class="badge bg-success">Current</span>
          @else
            <span class="badge bg-secondary">Past</span>
          @endif
        </div></div>
        @if($academicHistory->remarks)
        <div class="col-md-12"><strong>Remarks</strong><div>{{ $academicHistory->remarks }}</div></div>
        @endif
        @if($academicHistory->teacher_comments)
        <div class="col-md-12"><strong>Teacher Comments</strong><div>{{ $academicHistory->teacher_comments }}</div></div>
        @endif
        @if($academicHistory->promotedBy)
        <div class="col-md-6"><strong>Promoted By</strong><div>{{ $academicHistory->promotedBy->name }}</div></div>
        @endif
        <div class="col-md-6"><strong>Created At</strong><div>{{ $academicHistory->created_at->format('M d, Y h:i A') }}</div></div>
      </div>
    </div>
  </div>
</div>
@endsection

