@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Print Notes',
            'icon' => 'bi bi-file-earmark-text',
            'subtitle' => 'Draft a message, select recipients, then print personalised notes with the school letterhead to issue to parents (e.g. fee reminders, meeting reminders).',
            'actions' => '<a href="' . route('communication.logs') . '" class="btn btn-ghost-strong"><i class="bi bi-clock-history"></i> Logs</a>'
        ])

        @include('communication.partials.flash')

        <div class="settings-card">
            <div class="card-body">
                @include('communication.partials.student-selector-modal')

                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-info-circle fs-5"></i>
                    <div>
                        <div class="fw-semibold">Printed notes for parents</div>
                        <small class="text-muted">Use placeholders like @{{ student_name }}, @{{ parent_name }}, @{{ outstanding_amount }}. Each student gets one note with the school letterhead. For fee reminders: check <strong>Only recipients with fee balance</strong> so students with no outstanding balance or overpayment are skipped; all other students still get the note.</small>
                    </div>
                </div>

                <form method="POST" action="{{ route('communication.notes.print') }}" id="notesForm" class="row g-4" target="_blank">
                    @csrf

                    <div class="col-12">
                        <label class="form-label fw-semibold">Note title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Fee Reminder – Term 1 2026" required maxlength="255">
                        <small class="text-muted">Shown at the top of each printed note.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="notes-message" rows="10" class="form-control" placeholder="Dear Parent/Guardian of @{{ parent_name }},&#10;&#10;This is to remind you that fees for @{{ student_name }} (@{{ class_name }}) are outstanding.&#10;&#10;Outstanding amount: Ksh @{{ outstanding_amount }}.&#10;&#10;Please clear the balance at your earliest convenience.&#10;&#10;@{{ school_name }}&#10;@{{ date }}" required>{{ old('message') }}</textarea>
                        <small class="text-muted">Use placeholders below to personalise each note.</small>
                    </div>

                    <div class="col-12">
                        @include('communication.templates.partials.placeholder-selector', [
                            'systemPlaceholders' => $systemPlaceholders,
                            'customPlaceholders' => $customPlaceholders,
                            'targetField' => 'notes-message',
                        ])
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Recipients <span class="text-danger">*</span></label>
                        <select name="target" id="notes-target" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="parents" {{ old('target') === 'parents' ? 'selected' : '' }}>All students (one note per student)</option>
                            <option value="class" {{ old('target') === 'class' ? 'selected' : '' }}>Specific class</option>
                            <option value="student" {{ old('target') === 'student' ? 'selected' : '' }}>Single student</option>
                            <option value="specific_students" {{ old('target') === 'specific_students' ? 'selected' : '' }}>Select students</option>
                        </select>
                        <small class="text-muted d-block mt-1">One printed note per student, addressed to parent/guardian.</small>
                    </div>

                    <div class="col-lg-4 notes-target-field notes-target-class d-none">
                        <label class="form-label fw-semibold">Class</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">-- Select class --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('classroom_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 notes-target-field notes-target-student d-none">
                        <label class="form-label fw-semibold">Student</label>
                        <select name="student_id" class="form-select">
                            <option value="">-- Select student --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name }} ({{ $student->admission_number ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 notes-target-field notes-target-specific_students d-none">
                        <label class="form-label fw-semibold">Select students</label>
                        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
                            <i class="bi bi-people-fill"></i> Open student selector
                        </button>
                        <input type="hidden" name="selected_student_ids" id="notesSelectedStudentIds" value="{{ old('selected_student_ids') }}">
                        <div id="notesSelectedStudentsDisplay" class="mt-2 d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-semibold">Selected:</small>
                                <span class="badge bg-primary" id="notesSelectedStudentsBadge">0</span>
                            </div>
                            <div id="notesSelectedStudentsList" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    @include('communication.partials.fee-balance-exclude-filters')
                    @include('communication.partials.exclude-student-modal')

                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-printer"></i> Print notes
                        </button>
                        <a href="{{ route('communication.send.email') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetSelect = document.getElementById('notes-target');
    const targetFields = document.querySelectorAll('.notes-target-field');
    const notesSelectedStudentIdsInput = document.getElementById('notesSelectedStudentIds');
    const notesSelectedStudentsDisplay = document.getElementById('notesSelectedStudentsDisplay');
    const notesSelectedStudentsList = document.getElementById('notesSelectedStudentsList');
    const notesSelectedStudentsBadge = document.getElementById('notesSelectedStudentsBadge');

    function toggleTargetFields() {
        const target = targetSelect ? targetSelect.value : '';
        targetFields.forEach(function(el) {
            const show = el.classList.contains('notes-target-class') && target === 'class'
                || el.classList.contains('notes-target-student') && target === 'student'
                || el.classList.contains('notes-target-specific_students') && target === 'specific_students';
            el.classList.toggle('d-none', !show);
        });
        if (notesSelectedStudentsDisplay) {
            notesSelectedStudentsDisplay.classList.toggle('d-none', target !== 'specific_students' || !notesSelectedStudentIdsInput || !notesSelectedStudentIdsInput.value.trim());
        }
    }

    if (targetSelect) {
        targetSelect.addEventListener('change', toggleTargetFields);
    }
    toggleTargetFields();

    document.addEventListener('studentsSelected', function(event) {
        const studentIds = event.detail.studentIds || [];
        if (notesSelectedStudentIdsInput) {
            notesSelectedStudentIdsInput.value = studentIds.join(',');
        }
        if (notesSelectedStudentsBadge) {
            notesSelectedStudentsBadge.textContent = studentIds.length;
        }
        if (notesSelectedStudentsDisplay) {
            notesSelectedStudentsDisplay.classList.toggle('d-none', studentIds.length === 0);
        }
        if (notesSelectedStudentsList) {
            notesSelectedStudentsList.innerHTML = '';
        }
        toggleTargetFields();
    });

    document.getElementById('notesForm').addEventListener('submit', function(e) {
        const target = document.getElementById('notes-target').value;
        if (target === 'specific_students') {
            const ids = document.getElementById('notesSelectedStudentIds').value;
            if (!ids || ids.trim() === '') {
                e.preventDefault();
                alert('Please select at least one student using the student selector.');
                return false;
            }
        }
        if (target === 'class') {
            const classId = document.querySelector('select[name="classroom_id"]').value;
            if (!classId) {
                e.preventDefault();
                alert('Please select a class.');
                return false;
            }
        }
        if (target === 'student') {
            const studentId = document.querySelector('select[name="student_id"]').value;
            if (!studentId) {
                e.preventDefault();
                alert('Please select a student.');
                return false;
            }
        }
    });
});
</script>
@endpush
@endsection
