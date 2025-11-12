@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Lesson Plan</h1>
        <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.lesson-plans.update', $lesson_plan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               value="{{ old('title', $lesson_plan->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Planned Date <span class="text-danger">*</span></label>
                        <input type="date" name="planned_date" class="form-control @error('planned_date') is-invalid @enderror" 
                               value="{{ old('planned_date', $lesson_plan->planned_date->format('Y-m-d')) }}" required>
                        @error('planned_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Actual Date</label>
                        <input type="date" name="actual_date" class="form-control" 
                               value="{{ old('actual_date', $lesson_plan->actual_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="planned" {{ old('status', $lesson_plan->status) == 'planned' ? 'selected' : '' }}>Planned</option>
                            <option value="in_progress" {{ old('status', $lesson_plan->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status', $lesson_plan->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $lesson_plan->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Execution Status</label>
                        <select name="execution_status" class="form-select">
                            <option value="">Select</option>
                            <option value="excellent" {{ old('execution_status', $lesson_plan->execution_status) == 'excellent' ? 'selected' : '' }}>Excellent</option>
                            <option value="good" {{ old('execution_status', $lesson_plan->execution_status) == 'good' ? 'selected' : '' }}>Good</option>
                            <option value="fair" {{ old('execution_status', $lesson_plan->execution_status) == 'fair' ? 'selected' : '' }}>Fair</option>
                            <option value="poor" {{ old('execution_status', $lesson_plan->execution_status) == 'poor' ? 'selected' : '' }}>Poor</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" 
                               value="{{ old('duration_minutes', $lesson_plan->duration_minutes) }}" min="1" max="480">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Learning Objectives</label>
                    <textarea name="learning_objectives" class="form-control" rows="3">{{ old('learning_objectives', is_array($lesson_plan->learning_objectives) ? implode("\n", $lesson_plan->learning_objectives) : $lesson_plan->learning_objectives) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Learning Outcomes</label>
                    <textarea name="learning_outcomes" class="form-control" rows="3">{{ old('learning_outcomes', $lesson_plan->learning_outcomes) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Introduction</label>
                    <textarea name="introduction" class="form-control" rows="3">{{ old('introduction', $lesson_plan->introduction) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lesson Development</label>
                    <textarea name="lesson_development" class="form-control" rows="5">{{ old('lesson_development', $lesson_plan->lesson_development) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assessment</label>
                    <textarea name="assessment" class="form-control" rows="3">{{ old('assessment', $lesson_plan->assessment) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Conclusion</label>
                    <textarea name="conclusion" class="form-control" rows="2">{{ old('conclusion', $lesson_plan->conclusion) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reflection</label>
                    <textarea name="reflection" class="form-control" rows="3">{{ old('reflection', $lesson_plan->reflection) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Challenges</label>
                    <textarea name="challenges" class="form-control" rows="2">{{ old('challenges', $lesson_plan->challenges) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Improvements</label>
                    <textarea name="improvements" class="form-control" rows="2">{{ old('improvements', $lesson_plan->improvements) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Lesson Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


