@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('inventory.requirement-templates.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Requirement Templates
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">New Requirement</h1>
                    <p class="text-muted">Specify what each learner should provide at the beginning of the term.</p>

                    <form method="POST" action="{{ route('inventory.requirement-templates.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Requirement Type</label>
                                <select name="requirement_type_id" class="form-select" required>
                                    <option value="">Select type</option>
                                    @foreach($requirementTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('requirement_type_id') == $type->id)>
                                            {{ $type->name }} • {{ $type->category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Classroom</label>
                                <select name="classroom_id" class="form-select">
                                    <option value="">All classes</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>
                                            {{ $classroom->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Academic Year</label>
                                <select name="academic_year_id" class="form-select">
                                    <option value="">Any</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Term</label>
                                <select name="term_id" class="form-select">
                                    <option value="">Any</option>
                                    @foreach($terms as $term)
                                        <option value="{{ $term->id }}" @selected(old('term_id') == $term->id)>{{ $term->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Student Category</label>
                                <select name="student_type" class="form-select" required>
                                    @foreach(['new' => 'New students', 'existing' => 'Existing students', 'both' => 'All students'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('student_type', 'both') == $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Brand (optional)</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" placeholder="E.g. A4 Spiral">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity per Student</label>
                                <input type="number" step="0.01" min="0" name="quantity_per_student" class="form-control" required value="{{ old('quantity_per_student', 1) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" class="form-control" required value="{{ old('unit', 'pcs') }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes / Specifications</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Colour, size, packaging, etc.">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" name="leave_with_teacher" id="leaveWithTeacher" {{ old('leave_with_teacher') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="leaveWithTeacher">
                                        Keep items in school inventory (not returned to student)
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_verification_only" id="verificationOnly" {{ old('is_verification_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="verificationOnly">
                                        Verification only (return to parent/student)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duplicate to other classes (optional)</label>
                                <select name="duplicate_to_classes[]" class="form-select" multiple data-bs-toggle="tooltip" title="Hold CTRL/CMD to select multiple">
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Selected classes will get the same requirement automatically.</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('inventory.requirement-templates.index') }}" class="btn btn-light">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-check2-circle"></i> Save Requirement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

