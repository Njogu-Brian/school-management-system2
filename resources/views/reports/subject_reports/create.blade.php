@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Subject Report</h3>
            <div class="text-muted">Weekly subject status</div>
        </div>
        <a href="{{ route('reports.subject-reports.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.subject-reports.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Week Ending</label>
                        <input type="date" name="week_ending" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Campus</label>
                        <select name="campus" class="form-select">
                            <option value="">Select campus</option>
                            <option value="lower">Lower</option>
                            <option value="upper">Upper</option>
                        </select>
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
                        <label class="form-label">Teacher</label>
                        <select name="staff_id" class="form-select">
                            <option value="">Select teacher</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Topics Covered</label>
                        <textarea name="topics_covered" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Syllabus Status</label>
                        <select name="syllabus_status" class="form-select">
                            <option value="">Select</option>
                            <option value="On Track">On Track</option>
                            <option value="Slightly Behind">Slightly Behind</option>
                            <option value="Behind">Behind</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Strong %</label>
                        <input type="number" step="0.01" name="strong_percent" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Average %</label>
                        <input type="number" step="0.01" name="average_percent" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Struggling %</label>
                        <input type="number" step="0.01" name="struggling_percent" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Homework Given</label>
                        <select name="homework_given" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Test Done</label>
                        <select name="test_done" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marking Done</label>
                        <select name="marking_done" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Main Challenge</label>
                        <input type="text" name="main_challenge" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Needed</label>
                        <input type="text" name="support_needed" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Academic Group</label>
                        <input type="text" name="academic_group" class="form-control">
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Save Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
