@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Class Requirements</div>
                <h1>Edit Assignment</h1>
            </div>
            <a href="{{ route('inventory.requirement-template-assignments.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Assignments
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.requirement-template-assignments.update', $assignment) }}" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label">Requirement Item</label>
                        <select name="requirement_template_id" class="form-select" required>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" @selected(old('requirement_template_id', $assignment->requirement_template_id) == $template->id)>
                                    {{ $template->requirementType?->name ?? 'Requirement' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Classroom</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">All classes</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $assignment->classroom_id) == $classroom->id)>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Academic Year</label>
                        <select name="academic_year_id" class="form-select" id="academicYearSelect">
                            <option value="">Any</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $assignment->academic_year_id) == $year->id)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Term</label>
                        <select name="term_id" class="form-select" id="termSelect" data-selected="{{ old('term_id', $assignment->term_id) }}">
                            <option value="">Any</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Student Category</label>
                        <select name="student_type" class="form-select" required>
                            @foreach(['new' => 'New students', 'existing' => 'Existing students', 'both' => 'All students'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('student_type', $assignment->student_type) == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Brand (optional)</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand', $assignment->brand) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity per Student</label>
                        <input type="number" step="0.01" min="0" name="quantity_per_student" class="form-control" required value="{{ old('quantity_per_student', $assignment->quantity_per_student) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-control" required value="{{ old('unit', $assignment->unit) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes / Specifications</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $assignment->notes) }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="leave_with_teacher" id="leaveWithTeacher" {{ old('leave_with_teacher', $assignment->leave_with_teacher) ? 'checked' : '' }}>
                            <label class="form-check-label" for="leaveWithTeacher">Keep items in school inventory</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" name="is_verification_only" id="verificationOnly" {{ old('is_verification_only', $assignment->is_verification_only) ? 'checked' : '' }}>
                            <label class="form-check-label" for="verificationOnly">Verification only</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="isActive" {{ old('is_active', $assignment->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.requirement-template-assignments.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button class="btn btn-settings-primary" type="submit">
                            <i class="bi bi-check2-circle"></i> Update Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const academicYearSelect = document.getElementById('academicYearSelect');
        const termSelect = document.getElementById('termSelect');
        if (!academicYearSelect || !termSelect) return;

        const defaultAnyOptionHtml = '<option value="">Any</option>';

        function setOptions(terms) {
            const selectedTermId = termSelect.getAttribute('data-selected');
            let html = defaultAnyOptionHtml;
            for (const t of terms) {
                const selected = selectedTermId && String(t.id) === String(selectedTermId) ? ' selected' : '';
                html += `<option value="${t.id}"${selected}>${t.name}</option>`;
            }
            termSelect.innerHTML = html;
        }

        async function loadTerms() {
            const yearId = academicYearSelect.value;
            if (!yearId) {
                termSelect.innerHTML = defaultAnyOptionHtml;
                return;
            }

            const res = await fetch(`{{ url('/inventory/academic-years') }}/${yearId}/terms`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                termSelect.innerHTML = defaultAnyOptionHtml;
                return;
            }
            const data = await res.json();
            setOptions(data.terms || []);
        }

        academicYearSelect.addEventListener('change', () => {
            termSelect.setAttribute('data-selected', '');
            loadTerms();
        });

        loadTerms();
    })();
</script>
@endpush

