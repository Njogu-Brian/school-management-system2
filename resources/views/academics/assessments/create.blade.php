@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Assessment</h3>
            <div class="text-muted">Capture assessment results</div>
        </div>
        <a href="{{ route('academics.assessments.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('academics.assessments.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Assessment Date</label>
                        <input type="date" name="assessment_date" class="form-control" value="{{ old('assessment_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Week Ending</label>
                        <input type="date" name="week_ending" class="form-control" value="{{ old('week_ending') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select name="classroom_id" class="form-select" required>
                            <option value="">Select class</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name ?? $student->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Assessment Type</label>
                        <input type="text" name="assessment_type" class="form-control" value="{{ old('assessment_type') }}" placeholder="CAT/Quiz/Exam/Assignment">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Score</label>
                        <input type="number" step="0.01" name="score" class="form-control" value="{{ old('score') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Out Of</label>
                        <input type="number" step="0.01" name="out_of" class="form-control" value="{{ old('out_of') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Teacher</label>
                        <select name="staff_id" class="form-select">
                            <option value="">Select teacher (optional)</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Academic Group</label>
                        <input type="text" name="academic_group" class="form-control" value="{{ old('academic_group') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Save Assessment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
