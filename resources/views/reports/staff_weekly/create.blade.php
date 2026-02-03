@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Staff Weekly Report</h3>
            <div class="text-muted">Weekly staff performance</div>
        </div>
        <a href="{{ route('reports.staff-weekly.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.staff-weekly.store') }}">
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
                        <label class="form-label">Teacher</label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">Select teacher</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">On Time All Week</label>
                        <select name="on_time_all_week" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lessons Missed</label>
                        <input type="number" name="lessons_missed" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Books Marked</label>
                        <select name="books_marked" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Schemes Updated</label>
                        <select name="schemes_updated" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class Control</label>
                        <select name="class_control" class="form-select">
                            <option value="">Select</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">General Performance</label>
                        <select name="general_performance" class="form-select">
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
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
