@include('communication.partials.student-selector-modal')

<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-megaphone fs-5"></i>
    <div>
        <div class="fw-semibold">Bulk SMS compose</div>
        <small class="text-muted">Use templates for repeat sends or switch to manual and drop in placeholders.</small>
    </div>
</div>

<div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="btn-group" role="group" aria-label="Compose mode">
        <input class="btn-check" type="radio" name="sms_mode" id="sms_mode_template" value="template" checked>
        <label class="btn btn-outline-primary" for="sms_mode_template"><i class="bi bi-layout-text-sidebar-reverse"></i> Use Template</label>
        <input class="btn-check" type="radio" name="sms_mode" id="sms_mode_manual" value="manual">
        <label class="btn btn-outline-primary" for="sms_mode_manual"><i class="bi bi-pencil-square"></i> Manual Compose</label>
    </div>
    <span class="text-muted small">Placeholders work in templates and manual body.</span>
</div>

<form method="POST" action="{{ route('communication.send.sms.submit') }}" class="row g-4">
    @csrf
    <input type="hidden" name="sender_id" value="{{ env('SMS_SENDER_ID', config('app.name')) }}">

    {{-- Template mode --}}
    <div class="col-lg-4 sms-mode sms-mode-template">
        <label class="form-label fw-semibold">Template</label>
        <select name="template_id" class="form-select">
            <option value="">-- None --</option>
            @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>
                    {{ $tpl->title }} ({{ strtoupper($tpl->type) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Loads content from the template.</small>
    </div>

    {{-- Manual mode --}}
    <div class="col-lg-8 sms-mode sms-mode-manual d-none">
        <label class="form-label fw-semibold">Message *</label>
        <textarea name="message" rows="5" class="form-control" id="sms-body" placeholder="160 characters per SMS segment.">{{ old('message') }}</textarea>
        <div class="d-flex justify-content-between text-muted small mt-1">
            <span>Placeholders allowed (see below).</span>
            <span id="sms-counter">0 chars</span>
        </div>
    </div>

    {{-- Common targeting --}}
    <div class="col-lg-4">
        <label class="form-label fw-semibold">Target</label>
        <select name="target" id="sms-target" class="form-select" required>
            <option value="">-- Select Target --</option>
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>All students</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
            <option value="specific_students" {{ old('target')==='specific_students' ? 'selected' : '' }}>Select Specific Students</option>
            <option value="custom" {{ old('target')==='custom' ? 'selected' : '' }}>Custom numbers</option>
        </select>
    </div>
    <div class="col-lg-4">
        <label class="form-label fw-semibold">Sender ID</label>
        <select name="sender_id" class="form-select">
            <option value="">Default ({{ config('services.sms.sender_id', 'ROYAL_KINGS') }})</option>
            <option value="finance">Finance ({{ config('services.sms.sender_id_finance', 'rks_finance') }})</option>
        </select>
        <small class="text-muted d-block mt-1">Finance communications? pick Finance sender.</small>
    </div>

    <div class="col-lg-4 sms-target-field sms-target-class d-none">
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
    <div class="col-lg-4 sms-target-field sms-target-student d-none">
        <label class="form-label fw-semibold">Student</label>
        <select name="student_id" class="form-select">
            <option value="">-- Select Student --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Sends to the student's parent contacts.</small>
    </div>
    <div class="col-12 sms-target-field sms-target-specific_students d-none">
        <label class="form-label fw-semibold">Select Multiple Students</label>
        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
            <i class="bi bi-people-fill"></i> Open Student Selector
        </button>
        <input type="hidden" name="selected_student_ids" id="smsSelectedStudentIds" value="{{ old('selected_student_ids') }}">
        <div id="smsSelectedStudentsDisplay" class="mt-2 d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted fw-semibold">Selected Students:</small>
                <span class="badge bg-primary" id="smsSelectedStudentsBadge">0</span>
            </div>
            <div id="smsSelectedStudentsList" class="d-flex flex-wrap gap-2"></div>
        </div>
        <small class="text-muted d-block mt-1">Click to search and select multiple students. SMS will be sent to parents of selected students.</small>
    </div>
    <div class="col-12 sms-target-field sms-target-custom d-none">
        <label class="form-label fw-semibold">Custom Numbers</label>
        <textarea name="custom_numbers" class="form-control" rows="3" placeholder="+2547..., +2547...">{{ old('custom_numbers') }}</textarea>
        <small class="text-muted d-block mt-1">Use full country code; plus sign is optional.</small>
    </div>

    @include('communication.partials.fee-balance-exclude-filters')
    @include('communication.partials.exclude-student-modal')

    {{-- Schedule --}}
    <div class="col-lg-3">
        <label class="form-label fw-semibold">Schedule</label>
        <select name="schedule" id="sms-schedule" class="form-select">
            <option value="now" {{ old('schedule')==='now' ? 'selected' : '' }}>Send now</option>
            <option value="later" {{ old('schedule')==='later' ? 'selected' : '' }}>Schedule</option>
        </select>
    </div>
    <div class="col-lg-3 sms-schedule-later-field d-none">
        <label class="form-label fw-semibold">Send At</label>
        <input type="datetime-local" name="send_at" class="form-control" value="{{ old('send_at') }}">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="button" class="btn btn-outline-primary" id="preview-sms-btn" style="min-width: 120px;">
            <i class="bi bi-eye"></i> Preview
        </button>
        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-send"></i> Send SMS</button>
    </div>
</form>

<div class="settings-card mt-4">
    @include('communication.templates.partials.placeholder-selector', [
        'systemPlaceholders' => $systemPlaceholders ?? [],
        'customPlaceholders' => $customPlaceholders ?? collect(),
        'targetField' => 'sms-body'
    ])
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeTemplate = document.getElementById('sms_mode_template');
    const modeManual = document.getElementById('sms_mode_manual');
    const modeBlocks = {
        template: document.querySelectorAll('.sms-mode-template'),
        manual: document.querySelectorAll('.sms-mode-manual')
    };
    const targetSelect = document.getElementById('sms-target');
    const scheduleSelect = document.getElementById('sms-schedule');
    const targetFields = {
        class: document.querySelectorAll('.sms-target-class'),
        student: document.querySelectorAll('.sms-target-student'),
        specific_students: document.querySelectorAll('.sms-target-specific_students'),
        custom: document.querySelectorAll('.sms-target-custom')
    };
    const scheduleLater = document.querySelectorAll('.sms-schedule-later-field');
    const smsBody = document.getElementById('sms-body');
    const smsCounter = document.getElementById('sms-counter');

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

    function updateCounter() {
        if (!smsBody || !smsCounter) return;
        const len = smsBody.value.length;
        smsCounter.innerText = `${len} chars`;
    }

    if (modeTemplate && modeManual) {
        modeTemplate.addEventListener('change', refreshMode);
        modeManual.addEventListener('change', refreshMode);
        refreshMode();
    }
    if (targetSelect) { targetSelect.addEventListener('change', refreshTargetFields); refreshTargetFields(); }
    if (scheduleSelect) { scheduleSelect.addEventListener('change', refreshScheduleFields); refreshScheduleFields(); }
    if (smsBody) { smsBody.addEventListener('input', updateCounter); updateCounter(); }

    // Placeholder click -> insert at caret (preferred sms body) and keep copy fallback
    const smsForm = document.querySelector('form[action="{{ route('communication.send.sms.submit') }}"]');
    const placeholderButtons = document.querySelectorAll('.placeholder-chip');

    function insertAtCursor(field, text) {
        if (!field) return false;
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;
        const before = field.value.slice(0, start);
        const after = field.value.slice(end);
        field.value = before + text + after;
        const caret = start + text.length;
        field.setSelectionRange(caret, caret);
        field.focus();
        return true;
    }

    function getPreferredField() {
        const active = document.activeElement;
        if (active && active.tagName === 'TEXTAREA') return active;
        if (smsBody) return smsBody;
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

    // Smart placeholder validation before submit
    // Use escaped Blade syntax so these remain literal placeholders
    const studentPlaceholders = ['@{{student_name}}', '@{{class_name}}', '@{{parent_name}}'];
    const genericPlaceholders = ['@{{school_name}}', '@{{date}}'];

    function validatePlaceholders(evt) {
        if (!smsForm) return;
        const target = targetSelect?.value;
        if (!target) return;

        const messageText = smsBody?.value || '';
        const used = placeholderButtons
            ? Array.from(placeholderButtons).map(b => b.dataset.value).filter(ph => messageText.includes(ph))
            : [];

        if (!used.length) return;

        const usesStudentData = used.some(ph => studentPlaceholders.includes(ph));
        const onlyGeneric = used.every(ph => genericPlaceholders.includes(ph));

        // Require selections when needed
        if (target === 'student' && usesStudentData) {
            const studentSel = document.querySelector('select[name="student_id"]');
            if (!studentSel || !studentSel.value) {
                evt.preventDefault();
                alert('Select a student to use student/parent/class placeholders.');
                return;
            }
        }
        if (target === 'class' && usesStudentData) {
            const classSel = document.querySelector('select[name="classroom_id"]');
            if (!classSel || !classSel.value) {
                evt.preventDefault();
                alert('Select a class to use class/student placeholders.');
                return;
            }
        }

        // Block unsupported targets for student placeholders
        if (usesStudentData && (target === 'staff' || target === 'custom')) {
            evt.preventDefault();
            alert('Student/parent/class placeholders need a student/parent/class target, not staff/custom.');
            return;
        }

        // If custom with non-generic placeholders, warn
        if (target === 'custom' && !onlyGeneric) {
            evt.preventDefault();
            alert('Custom numbers can only use @{{school_name}} or @{{date}} placeholders.');
            return;
        }
    }

    if (smsForm) {
        smsForm.addEventListener('submit', validatePlaceholders);
    }

    // Preview functionality for SMS
    const previewBtn = document.getElementById('preview-sms-btn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = smsForm;
            if (!form) {
                alert('Form not found. Please refresh the page.');
                return;
            }
            
            const formData = new FormData(form);
            
            // Get message from textarea (works for both template and manual mode)
            let message = smsBody?.value || '';
            
            // If no message in textarea and template is selected, try to get from template
            if (!message) {
                const templateId = formData.get('template_id');
                if (templateId) {
                    alert('Please enter a message or ensure template content is loaded in the message field.');
                    return;
                } else {
                    alert('Please enter a message to preview.');
                    return;
                }
            }

            // Validate target is selected
            const target = formData.get('target');
            if (!target) {
                alert('Please select a target (e.g., Select Specific Students, Single Student, etc.)');
                return;
            }

            // Check if we have student selection for student-specific targets
            if (target === 'student' && !formData.get('student_id')) {
                alert('Please select a student to preview the message.');
                return;
            }
            
            if (target === 'class' && !formData.get('classroom_id')) {
                alert('Please select a classroom to preview the message.');
                return;
            }
            
            if (target === 'specific_students') {
                const selectedIds = formData.get('selected_student_ids');
                if (!selectedIds || selectedIds.trim() === '') {
                    alert('Please select at least one student to preview the message.');
                    return;
                }
            }

            // Create preview form
            const previewForm = document.createElement('form');
            previewForm.method = 'POST';
            previewForm.action = '{{ route("communication.preview") }}';
            previewForm.style.display = 'none';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            previewForm.appendChild(csrfInput);

            // Add message
            const messageInput = document.createElement('input');
            messageInput.type = 'hidden';
            messageInput.name = 'message';
            messageInput.value = message;
            previewForm.appendChild(messageInput);

            // Add channel
            const channelInput = document.createElement('input');
            channelInput.type = 'hidden';
            channelInput.name = 'channel';
            channelInput.value = 'sms';
            previewForm.appendChild(channelInput);

            // Add target data
            ['target', 'classroom_id', 'student_id', 'selected_student_ids', 'template_id', 'fee_balance_only', 'exclude_student_ids'].forEach(field => {
                const value = formData.get(field);
                if (value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = field;
                    input.value = value;
                    previewForm.appendChild(input);
                }
            });

            document.body.appendChild(previewForm);
            previewForm.submit();
        });
    }

    // Handle student selection from modal for SMS
    const smsSelectedStudentIdsInput = document.getElementById('smsSelectedStudentIds');
    const smsSelectedStudentsDisplay = document.getElementById('smsSelectedStudentsDisplay');
    const smsSelectedStudentsList = document.getElementById('smsSelectedStudentsList');
    const smsSelectedStudentsBadge = document.getElementById('smsSelectedStudentsBadge');

    document.addEventListener('studentsSelected', function(event) {
        const studentIds = event.detail.studentIds;
        
        if (smsSelectedStudentIdsInput) {
            smsSelectedStudentIdsInput.value = studentIds.join(',');
        }
        
        // Update display
        if (studentIds.length > 0 && smsSelectedStudentsDisplay) {
            smsSelectedStudentsDisplay.classList.remove('d-none');
            smsSelectedStudentsBadge.textContent = studentIds.length;
            
            // Show student names
            smsSelectedStudentsList.innerHTML = '';
            studentIds.forEach(id => {
                const checkbox = document.querySelector(`#student_${id}`);
                if (checkbox) {
                    const label = checkbox.closest('.student-item');
                    const studentName = label.querySelector('.fw-semibold')?.textContent.trim() || `Student ${id}`;
                    
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary';
                    badge.textContent = studentName;
                    smsSelectedStudentsList.appendChild(badge);
                }
            });
        } else if (smsSelectedStudentsDisplay) {
            smsSelectedStudentsDisplay.classList.add('d-none');
        }
    });
});
</script>
@endpush
