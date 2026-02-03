@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Student Follow-Up</h3>
            <div class="text-muted">Weekly student concerns</div>
        </div>
        <a href="{{ route('reports.student-followups.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.student-followups.store') }}">
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
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name ?? $student->name }}</option>
                            @endforeach
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

                    <div class="col-md-4">
                        <label class="form-label">Academic Concern</label>
                        <select name="academic_concern" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Behavior Concern</label>
                        <select name="behavior_concern" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parent Contacted</label>
                        <select name="parent_contacted" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Action Taken</label>
                        <input type="text" name="action_taken" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Progress Status</label>
                        <select name="progress_status" class="form-select">
                            <option value="">Select</option>
                            <option value="Improving">Improving</option>
                            <option value="Same">Same</option>
                            <option value="Worse">Worse</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Save Follow-Up</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
