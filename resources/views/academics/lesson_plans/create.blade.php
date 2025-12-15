@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Lesson Plan</h1>
        <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.lesson-plans.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Scheme of Work (Optional)</label>
                        <select name="scheme_of_work_id" class="form-select">
                            <option value="">None</option>
                            @foreach($schemes as $scheme)
                                <option value="{{ $scheme->id }}" {{ old('scheme_of_work_id') == $scheme->id ? 'selected' : '' }}>
                                    {{ $scheme->title }} - {{ $scheme->classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Classroom <span class="text-danger">*</span></label>
                        <select name="classroom_id" class="form-select @error('classroom_id') is-invalid @enderror" required>
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" {{ old('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classroom_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
                            <option value="">Select Year</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYearId ?? null) == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term_id" class="form-select @error('term_id') is-invalid @enderror" required>
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id', $currentTermId ?? null) == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('term_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Lesson Number</label>
                        <input type="text" name="lesson_number" class="form-control" value="{{ old('lesson_number') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Planned Date <span class="text-danger">*</span></label>
                        <input type="date" name="planned_date" class="form-control @error('planned_date') is-invalid @enderror" 
                               value="{{ old('planned_date') }}" required>
                        @error('planned_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">CBC Substrand (Optional)</label>
                    <select name="substrand_id" class="form-select">
                        <option value="">None</option>
                        @foreach($substrands as $substrand)
                            <option value="{{ $substrand->id }}" {{ old('substrand_id') == $substrand->id ? 'selected' : '' }}>
                                {{ $substrand->strand->name }} - {{ $substrand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Learning Objectives</label>
                    <textarea name="learning_objectives" class="form-control" rows="3" 
                              placeholder="Enter learning objectives (one per line)">{{ old('learning_objectives') }}</textarea>
                    <small class="text-muted">Enter one objective per line</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Learning Outcomes</label>
                    <textarea name="learning_outcomes" class="form-control" rows="3">{{ old('learning_outcomes') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Core Competencies</label>
                    <select name="core_competencies[]" class="form-select" multiple>
                        <option value="CC">Communication and Collaboration</option>
                        <option value="CTPS">Critical Thinking and Problem Solving</option>
                        <option value="CI">Creativity and Imagination</option>
                        <option value="CIT">Citizenship</option>
                        <option value="DL">Digital Literacy</option>
                        <option value="LTL">Learning to Learn</option>
                        <option value="SE">Self-Efficacy</option>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Introduction</label>
                    <textarea name="introduction" class="form-control" rows="3">{{ old('introduction') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lesson Development</label>
                    <textarea name="lesson_development" class="form-control" rows="5">{{ old('lesson_development') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assessment</label>
                    <textarea name="assessment" class="form-control" rows="3">{{ old('assessment') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Conclusion</label>
                    <textarea name="conclusion" class="form-control" rows="2">{{ old('conclusion') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Lesson Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


