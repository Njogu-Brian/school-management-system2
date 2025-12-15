@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Scheme of Work</h1>
        <a href="{{ route('academics.schemes-of-work.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Auto-Generate Option -->
    @can('schemes_of_work.generate')
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-magic"></i> Auto-Generate from CBC Curriculum</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('academics.schemes-of-work.generate') }}" method="POST" id="autoGenerateForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required id="auto_subject_id">
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Classroom <span class="text-danger">*</span></label>
                        <select name="classroom_id" class="form-select" required id="auto_classroom_id">
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select" required>
                            <option value="">Select Year</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term_id" class="form-select" required>
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Select Strands (Optional)</label>
                    <div id="auto_strands_container" class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted mb-0">Select subject and classroom to load strands</p>
                    </div>
                    <small class="text-muted">Leave empty to include all strands for the learning area</small>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Lessons (Optional)</label>
                        <input type="number" name="total_lessons" class="form-control" min="1" placeholder="Auto-calculate">
                        <small class="text-muted">Leave empty to auto-calculate from substrands</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lessons Multiplier (Optional)</label>
                        <input type="number" name="lessons_multiplier" class="form-control" step="0.1" min="0.1" max="2" value="1" placeholder="1.0">
                        <small class="text-muted">Multiply calculated lessons by this factor</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="generate_lesson_plans" value="1" id="generate_lesson_plans">
                        <label class="form-check-label" for="generate_lesson_plans">
                            Also generate lesson plans
                        </label>
                    </div>
                </div>

                <div id="lesson_plans_options" style="display: none;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lessons Per Week</label>
                            <input type="number" name="lessons_per_week" class="form-control" value="5" min="1" max="10">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Auto-generated description"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">General Remarks (Optional)</label>
                    <textarea name="general_remarks" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-magic"></i> Auto-Generate Scheme of Work
                </button>
            </form>
        </div>
    </div>
    @endcan

    <!-- Manual Create Option -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Manual Create</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('academics.schemes-of-work.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }} ({{ $subject->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
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
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
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

                    <div class="col-md-6">
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

                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                           value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">CBC Strands Coverage</label>
                    <select name="strands_coverage[]" id="strands_coverage" class="form-select" multiple>
                        <option value="">Select subject and classroom first to load strands</option>
                        @foreach($strands as $strand)
                            <option value="{{ $strand->id }}">{{ $strand->name }} ({{ $strand->code }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple. Strands will load after selecting subject and classroom.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">General Remarks</label>
                    <textarea name="general_remarks" class="form-control" rows="3">{{ old('general_remarks') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.schemes-of-work.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Scheme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.querySelector('select[name="subject_id"]');
    const classroomSelect = document.querySelector('select[name="classroom_id"]');
    const strandsSelect = document.getElementById('strands_coverage');

    function loadStrands() {
        const subjectId = subjectSelect.value;
        const classroomId = classroomSelect.value;

        if (!subjectId || !classroomId) {
            strandsSelect.innerHTML = '<option value="">Select subject and classroom first</option>';
            return;
        }

        fetch(`{{ route('academics.schemes-of-work.get-strands') }}?subject_id=${subjectId}&classroom_id=${classroomId}`)
            .then(response => response.json())
            .then(data => {
                strandsSelect.innerHTML = '';
                if (data.length === 0) {
                    strandsSelect.innerHTML = '<option value="">No strands found for this subject and classroom</option>';
                } else {
                    data.forEach(strand => {
                        const option = document.createElement('option');
                        option.value = strand.id;
                        option.textContent = `${strand.name} (${strand.code})`;
                        strandsSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading strands:', error);
                strandsSelect.innerHTML = '<option value="">Error loading strands</option>';
            });
    }

    subjectSelect.addEventListener('change', loadStrands);
    classroomSelect.addEventListener('change', loadStrands);

    // Auto-generation form handlers
    const autoSubjectSelect = document.getElementById('auto_subject_id');
    const autoClassroomSelect = document.getElementById('auto_classroom_id');
    const autoStrandsContainer = document.getElementById('auto_strands_container');
    const generateLessonPlansCheck = document.getElementById('generate_lesson_plans');
    const lessonPlansOptions = document.getElementById('lesson_plans_options');

    function loadAutoStrands() {
        const subjectId = autoSubjectSelect.value;
        const classroomId = autoClassroomSelect.value;

        if (!subjectId || !classroomId) {
            autoStrandsContainer.innerHTML = '<p class="text-muted mb-0">Select subject and classroom to load strands</p>';
            return;
        }

        fetch(`{{ route('academics.schemes-of-work.get-strands') }}?subject_id=${subjectId}&classroom_id=${classroomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    autoStrandsContainer.innerHTML = '<p class="text-muted mb-0">No strands found for this subject and classroom</p>';
                } else {
                    let html = '';
                    data.forEach(strand => {
                        html += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="strand_ids[]" value="${strand.id}" id="auto_strand_${strand.id}">
                                <label class="form-check-label" for="auto_strand_${strand.id}">
                                    ${strand.name} (${strand.code})
                                </label>
                            </div>
                        `;
                    });
                    autoStrandsContainer.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error loading strands:', error);
                autoStrandsContainer.innerHTML = '<p class="text-danger mb-0">Error loading strands</p>';
            });
    }

    autoSubjectSelect.addEventListener('change', loadAutoStrands);
    autoClassroomSelect.addEventListener('change', loadAutoStrands);

    generateLessonPlansCheck.addEventListener('change', function() {
        lessonPlansOptions.style.display = this.checked ? 'block' : 'none';
    });
});
</script>
@endsection

