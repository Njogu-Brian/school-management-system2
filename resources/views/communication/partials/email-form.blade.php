@include('communication.partials.student-selector-modal')

<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-envelope-paper fs-5"></i>
    <div>
        <div class="fw-semibold">Gmail-style compose</div>
        <small class="text-muted">Use templates for repeat sends, or switch to manual and insert placeholders below.</small>
    </div>
</div>

<div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="btn-group" role="group" aria-label="Compose mode">
        <input class="btn-check" type="radio" name="email_mode" id="email_mode_template" value="template" checked>
        <label class="btn btn-outline-primary" for="email_mode_template"><i class="bi bi-layout-text-sidebar-reverse"></i> Use Template</label>
        <input class="btn-check" type="radio" name="email_mode" id="email_mode_manual" value="manual">
        <label class="btn btn-outline-primary" for="email_mode_manual"><i class="bi bi-pencil-square"></i> Manual Compose</label>
    </div>
    <span class="text-muted small">Placeholders work in both templates and manual content.</span>
</div>

<form method="POST" action="{{ route('communication.send.email.submit') }}" enctype="multipart/form-data" class="row g-4">
    @csrf

    {{-- Template mode --}}
    <div class="col-lg-6 email-mode email-mode-template">
        <label class="form-label fw-semibold">Email Template</label>
        <select name="template_id" class="form-select">
            <option value="">-- Select Template --</option>
            @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>
                    {{ $tpl->title }} ({{ strtoupper($tpl->type) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Loads subject and body from the template.</small>
    </div>

    {{-- Manual mode --}}
    <div class="col-lg-6 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Email Title *</label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Term Updates">
        <small class="text-muted d-block mt-1">Shows as the email subject.</small>
    </div>
    <div class="col-lg-6 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Attachment (optional)</label>
        <input type="file" name="attachment" id="email-attachment" class="form-control" accept="image/*,video/*,.pdf,.doc,.docx">
        <small class="text-muted d-block mt-1">Images, videos, PDF, DOCX supported.</small>
        <div id="email-attachment-preview" class="mt-2 d-none"></div>
    </div>
    <div class="col-12 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Message *</label>
        <textarea name="message" id="email-body" rows="8" class="form-control" placeholder="Write your message...">{{ old('message') }}</textarea>
        <small class="text-muted d-block mt-1">Tip: insert placeholders like @{{student_name}} to personalize.</small>
    </div>

    {{-- Common targeting --}}
    <div class="col-lg-4">
        <label class="form-label fw-semibold">Target *</label>
        <select name="target" id="email-target" class="form-select" required>
            <option value="">-- Select Target --</option>
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>Parents (all)</option>
            <option value="students" {{ old('target')==='students' ? 'selected' : '' }}>Students (all)</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
            <option value="specific_students" {{ old('target')==='specific_students' ? 'selected' : '' }}>Select Specific Students</option>
            <option value="custom" {{ old('target')==='custom' ? 'selected' : '' }}>Custom email list</option>
        </select>
    </div>
    <div class="col-lg-4 email-target-field email-target-class d-none">
        <label class="form-label fw-semibold">Classroom</label>
        <select name="classroom_id" class="form-select">
            <option value="">-- Select Class --</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ old('classroom_id') == $class->id ? 'selected' : '' }}>
                    {{ $class->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-4 email-target-field email-target-student d-none">
        <label class="form-label fw-semibold">Student</label>
        <select name="student_id" class="form-select">
            <option value="">-- Select Student --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name ?? ($student->first_name.' '.$student->last_name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Sends to parents of the selected student.</small>
    </div>
    <div class="col-12 email-target-field email-target-specific_students d-none">
        <label class="form-label fw-semibold">Select Multiple Students</label>
        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
            <i class="bi bi-people-fill"></i> Open Student Selector
        </button>
        <input type="hidden" name="selected_student_ids" id="selectedStudentIds" value="{{ old('selected_student_ids') }}">
        <div id="selectedStudentsDisplay" class="mt-2 d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted fw-semibold">Selected Students:</small>
                <span class="badge bg-primary" id="selectedStudentsBadge">0</span>
            </div>
            <div id="selectedStudentsList" class="d-flex flex-wrap gap-2"></div>
        </div>
        <small class="text-muted d-block mt-1">Click to search and select multiple students. Email will be sent to parents of selected students.</small>
    </div>
    <div class="col-12 email-target-field email-target-custom d-none">
        <label class="form-label fw-semibold">Custom Emails</label>
        <textarea name="custom_emails" class="form-control" rows="3" placeholder="email1@example.com, email2@example.com">{{ old('custom_emails') }}</textarea>
        <small class="text-muted d-block mt-1">Comma-separated list.</small>
    </div>

    {{-- Schedule --}}
    <div class="col-lg-3">
        <label class="form-label fw-semibold">Send Timing</label>
        <select name="schedule" id="email-schedule" class="form-select">
            <option value="now" {{ old('schedule')==='now' ? 'selected' : '' }}>Send now</option>
            <option value="later" {{ old('schedule')==='later' ? 'selected' : '' }}>Schedule for later</option>
        </select>
    </div>
    <div class="col-lg-3 email-schedule-later-field d-none">
        <label class="form-label fw-semibold">Send At</label>
        <input type="datetime-local" name="send_at" class="form-control" value="{{ old('send_at') }}">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary"><i class="bi bi-send"></i> Send Email</button>
    </div>
</form>

<div class="settings-card mt-4">
    @include('communication.templates.partials.placeholder-selector', [
        'systemPlaceholders' => $systemPlaceholders ?? [],
        'customPlaceholders' => $customPlaceholders ?? collect(),
        'targetField' => 'email-body'
    ])
</div>

<div class="settings-card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Emoji Picker</h6>
        <span class="text-muted small">Click to insert</span>
    </div>
    <div class="card-body d-flex flex-wrap gap-2">
        @php $emojis = ['😊','🎉','👍','🙏','❤️','✅','📌','📅','🚌','🏫','💳','📎']; @endphp
        @foreach($emojis as $emo)
            <button type="button" class="btn btn-sm btn-outline-secondary emoji-chip" data-value="{{ $emo }}">{{ $emo }}</button>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeTemplate = document.getElementById('email_mode_template');
    const modeManual = document.getElementById('email_mode_manual');
    const modeBlocks = {
        template: document.querySelectorAll('.email-mode-template'),
        manual: document.querySelectorAll('.email-mode-manual')
    };
    const targetSelect = document.getElementById('email-target');
    const scheduleSelect = document.getElementById('email-schedule');
    const targetFields = {
        class: document.querySelectorAll('.email-target-class'),
        student: document.querySelectorAll('.email-target-student'),
        specific_students: document.querySelectorAll('.email-target-specific_students'),
        custom: document.querySelectorAll('.email-target-custom')
    };
    const scheduleLater = document.querySelectorAll('.email-schedule-later-field');

    function refreshTargetFields() {
        const val = targetSelect.value;
        Object.keys(targetFields).forEach(key => {
            targetFields[key].forEach(el => el.classList.add('d-none'));
        });
        if (val === 'class') targetFields.class.forEach(el => el.classList.remove('d-none'));
        if (val === 'student') targetFields.student.forEach(el => el.classList.remove('d-none'));
        if (val === 'specific_students') targetFields.specific_students.forEach(el => el.classList.remove('d-none'));
        if (val === 'custom') targetFields.custom.forEach(el => el.classList.remove('d-none'));
    }

    function refreshScheduleFields() {
        const val = scheduleSelect.value;
        scheduleLater.forEach(el => el.classList.toggle('d-none', val !== 'later'));
    }

    function refreshMode() {
        const useTemplate = modeTemplate.checked;
        modeBlocks.template.forEach(el => el.classList.toggle('d-none', !useTemplate));
        modeBlocks.manual.forEach(el => el.classList.toggle('d-none', useTemplate));
    }

    if (modeTemplate && modeManual) {
        modeTemplate.addEventListener('change', refreshMode);
        modeManual.addEventListener('change', refreshMode);
        refreshMode();
    }
    if (targetSelect) { targetSelect.addEventListener('change', refreshTargetFields); refreshTargetFields(); }
    if (scheduleSelect) { scheduleSelect.addEventListener('change', refreshScheduleFields); refreshScheduleFields(); }

    // Placeholder click -> insert at caret (preferred email body) and keep copy fallback
    const emailForm = document.querySelector('form[action="{{ route('communication.send.email.submit') }}"]');
    const placeholderButtons = document.querySelectorAll('.placeholder-chip');
    const emailBody = document.getElementById('email-body');
    const attachmentInput = document.getElementById('email-attachment');
    const attachmentPreview = document.getElementById('email-attachment-preview');

    function insertAtCursor(field, text) {
        if (!field) return false;
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;
        field.value = field.value.slice(0, start) + text + field.value.slice(end);
        const caret = start + text.length;
        field.setSelectionRange(caret, caret);
        field.focus();
        return true;
    }

    function getPreferredField() {
        const active = document.activeElement;
        if (active && active.tagName === 'TEXTAREA') return active;
        if (emailBody) return emailBody;
        return null;
    }

    placeholderButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.value;
            const targetField = getPreferredField();
            const inserted = insertAtCursor(targetField, val);
            if (!inserted) {
                navigator.clipboard?.writeText(val);
            }
            btn.classList.add('btn-primary','text-white');
            setTimeout(() => btn.classList.remove('btn-primary','text-white'), 600);
        });
    });

    document.querySelectorAll('.emoji-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.value;
            const targetField = getPreferredField();
            insertAtCursor(targetField, val);
        });
    });

    if (attachmentInput && attachmentPreview) {
        attachmentInput.addEventListener('change', () => {
            attachmentPreview.innerHTML = '';
            const file = attachmentInput.files?.[0];
            if (!file) {
                attachmentPreview.classList.add('d-none');
                return;
            }
            const url = URL.createObjectURL(file);
            let node = null;
            if (file.type.startsWith('image/')) {
                node = document.createElement('img');
                node.src = url;
                node.style.maxWidth = '220px';
                node.className = 'img-thumbnail';
            } else if (file.type.startsWith('video/')) {
                node = document.createElement('video');
                node.src = url;
                node.controls = true;
                node.style.maxWidth = '260px';
            } else {
                node = document.createElement('div');
                node.className = 'text-muted small';
                node.innerText = `Attached: ${file.name}`;
            }
            attachmentPreview.appendChild(node);
            attachmentPreview.classList.remove('d-none');
        });
    }

    // Smart placeholder validation before submit
    // Use escaped Blade syntax so these remain literal placeholders
    const studentPlaceholders = ['@{{student_name}}', '@{{class_name}}', '@{{parent_name}}', '@{{admission_no}}'];
    const staffPlaceholders = ['@{{staff_name}}', '@{{role}}'];
    const genericPlaceholders = ['@{{school_name}}', '@{{date}}'];

    function validatePlaceholders(evt) {
        if (!emailForm) return;
        const target = targetSelect?.value;
        if (!target) return;

        const messageText = emailBody?.value || '';
        const used = placeholderButtons
            ? Array.from(placeholderButtons).map(b => b.dataset.value).filter(ph => messageText.includes(ph))
            : [];

        if (!used.length) return;

        const usesStudent = used.some(ph => studentPlaceholders.includes(ph));
        const usesStaff = used.some(ph => staffPlaceholders.includes(ph));
        const onlyGeneric = used.every(ph => genericPlaceholders.includes(ph));

        if (usesStudent && (target === 'staff' || target === 'custom')) {
            evt.preventDefault();
            alert('Student/parent/class placeholders require student/parent/class targets, not staff/custom.');
            return;
        }

        if (usesStaff && target !== 'staff') {
            evt.preventDefault();
            alert('Staff placeholders require the Staff target.');
            return;
        }

        if (target === 'student' && usesStudent) {
            const studentSel = document.querySelector('select[name="student_id"]');
            if (!studentSel || !studentSel.value) {
                evt.preventDefault();
                alert('Select a student to use student/parent/class placeholders.');
                return;
            }
        }

        if (target === 'class' && usesStudent) {
            const classSel = document.querySelector('select[name="classroom_id"]');
            if (!classSel || !classSel.value) {
                evt.preventDefault();
                alert('Select a class to use class/student placeholders.');
                return;
            }
        }

        if (target === 'custom' && !onlyGeneric) {
            evt.preventDefault();
            alert('Custom email lists can only use @{{school_name}} or @{{date}} placeholders.');
            return;
        }
    }

    if (emailForm) {
        emailForm.addEventListener('submit', validatePlaceholders);
    }

    // Handle student selection from modal
    const selectedStudentIdsInput = document.getElementById('selectedStudentIds');
    const selectedStudentsDisplay = document.getElementById('selectedStudentsDisplay');
    const selectedStudentsList = document.getElementById('selectedStudentsList');
    const selectedStudentsBadge = document.getElementById('selectedStudentsBadge');

    document.addEventListener('studentsSelected', function(event) {
        const studentIds = event.detail.studentIds;
        
        if (selectedStudentIdsInput) {
            selectedStudentIdsInput.value = studentIds.join(',');
        }
        
        // Update display
        if (studentIds.length > 0 && selectedStudentsDisplay) {
            selectedStudentsDisplay.classList.remove('d-none');
            selectedStudentsBadge.textContent = studentIds.length;
            
            // Show student names
            selectedStudentsList.innerHTML = '';
            studentIds.forEach(id => {
                const checkbox = document.querySelector(`#student_${id}`);
                if (checkbox) {
                    const label = checkbox.closest('.student-item');
                    const studentName = label.querySelector('.fw-semibold')?.textContent.trim() || `Student ${id}`;
                    
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary';
                    badge.textContent = studentName;
                    selectedStudentsList.appendChild(badge);
                }
            });
        } else if (selectedStudentsDisplay) {
            selectedStudentsDisplay.classList.add('d-none');
        }
    });
});
</script>
@endpush

