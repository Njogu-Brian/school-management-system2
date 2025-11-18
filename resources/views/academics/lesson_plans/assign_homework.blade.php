@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Assign Homework from Lesson Plan</h1>
        <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lesson Plan Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Subject:</th>
                            <td>{{ $lesson_plan->subject->name }}</td>
                        </tr>
                        <tr>
                            <th>Classroom:</th>
                            <td>{{ $lesson_plan->classroom->name }}</td>
                        </tr>
                        <tr>
                            <th>Title:</th>
                            <td>{{ $lesson_plan->title }}</td>
                        </tr>
                        <tr>
                            <th>Planned Date:</th>
                            <td>{{ $lesson_plan->planned_date->format('l, d M Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Assign Homework</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('academics.lesson-plans.assign-homework', $lesson_plan) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Homework Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', 'Homework - ' . $lesson_plan->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('instructions') is-invalid @enderror" 
                                      id="instructions" name="instructions" rows="5" required>{{ old('instructions', $lesson_plan->learning_outcomes ?? '') }}</textarea>
                            @error('instructions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" 
                                       value="{{ old('due_date', $lesson_plan->planned_date->addDays(1)->format('Y-m-d')) }}" 
                                       min="{{ date('Y-m-d') }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="max_score" class="form-label">Max Score (Optional)</label>
                                <input type="number" class="form-control @error('max_score') is-invalid @enderror" 
                                       id="max_score" name="max_score" value="{{ old('max_score') }}" min="1">
                                @error('max_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_late_submission" name="allow_late_submission" value="1" 
                                       {{ old('allow_late_submission', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_late_submission">
                                    Allow Late Submission
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target_scope" id="target_class" value="class" checked onchange="toggleStudentSelection()">
                                <label class="form-check-label" for="target_class">
                                    All Students in Class
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target_scope" id="target_students" value="students" onchange="toggleStudentSelection()">
                                <label class="form-check-label" for="target_students">
                                    Selected Students
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="student_selection" style="display: none;">
                            <label class="form-label">Select Students</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                @php
                                    $students = \App\Models\Student::where('classroom_id', $lesson_plan->classroom_id)->get();
                                @endphp
                                @foreach($students as $student)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="student_ids[]" 
                                           value="{{ $student->id }}" id="student_{{ $student->id }}">
                                    <label class="form-check-label" for="student_{{ $student->id }}">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Assign Homework
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Lesson Plan Info</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Subject:</strong> {{ $lesson_plan->subject->name }}<br>
                        <strong>Classroom:</strong> {{ $lesson_plan->classroom->name }}<br>
                        <strong>Date:</strong> {{ $lesson_plan->planned_date->format('d M Y') }}<br>
                        <strong>Duration:</strong> {{ $lesson_plan->duration_minutes }} minutes
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStudentSelection() {
    const targetStudents = document.getElementById('target_students');
    const studentSelection = document.getElementById('student_selection');
    
    if (targetStudents.checked) {
        studentSelection.style.display = 'block';
    } else {
        studentSelection.style.display = 'none';
        // Uncheck all students when class is selected
        document.querySelectorAll('input[name="student_ids[]"]').forEach(cb => {
            cb.checked = false;
        });
    }
}
</script>
@endsection

