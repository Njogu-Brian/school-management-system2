@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Class Report</h3>
            <div class="text-muted">Weekly class status</div>
        </div>
        <a href="{{ route('reports.class-reports.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.class-reports.store') }}">
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
                        <label class="form-label">Class Teacher</label>
                        <select name="class_teacher_id" class="form-select">
                            <option value="">Select teacher</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Total Learners</label>
                        <input type="number" name="total_learners" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Frequent Absentees</label>
                        <input type="number" name="frequent_absentees" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discipline Level</label>
                        <select name="discipline_level" class="form-select">
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Homework Completion</label>
                        <select name="homework_completion" class="form-select">
                            <option value="">Select</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Classroom Condition</label>
                        <select name="classroom_condition" class="form-select">
                            <option value="">Select</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Learners Struggling</label>
                        <input type="number" name="learners_struggling" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Learners Improved</label>
                        <input type="number" name="learners_improved" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parents to Contact</label>
                        <input type="number" name="parents_to_contact" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Academic Group</label>
                        <input type="text" name="academic_group" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
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
