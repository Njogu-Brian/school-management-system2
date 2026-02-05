@include('communication.partials.student-selector-modal')

<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-whatsapp fs-5"></i>
    <div>
        <div class="fw-semibold">WhatsApp-style compose</div>
        <small class="text-muted">Templates plus personalized placeholders; falls back to phone if no WhatsApp field.</small>
        <div class="mt-2">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> 
                <strong>Bulk sending:</strong> Messages are automatically rate-limited (5 seconds between messages) to respect account protection. 
                To send faster, disable account protection in <a href="{{ route('communication.wasender.sessions') }}" class="alert-link">WhatsApp Sessions</a>.
            </small>
        </div>
    </div>
</div>

<div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="btn-group" role="group" aria-label="Compose mode">
        <input class="btn-check" type="radio" name="wa_mode" id="wa_mode_template" value="template" checked>
        <label class="btn btn-outline-success" for="wa_mode_template"><i class="bi bi-layout-text-sidebar-reverse"></i> Use Template</label>
        <input class="btn-check" type="radio" name="wa_mode" id="wa_mode_manual" value="manual">
        <label class="btn btn-outline-success" for="wa_mode_manual"><i class="bi bi-pencil-square"></i> Manual Compose</label>
    </div>
    <span class="text-muted small">Placeholders work in both modes.</span>
</div>

<form method="POST" action="{{ route('communication.send.whatsapp.submit') }}" class="row g-4">
    @csrf

    {{-- Template --}}
    <div class="col-12 col-lg-4 wa-mode wa-mode-template">
        <label class="form-label fw-semibold">Template</label>
        <select name="template_id" class="form-select">
            <option value="">-- None --</option>
            @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>
                    {{ $tpl->title }} ({{ strtoupper($tpl->type) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Loads content when chosen.</small>
    </div>

    {{-- Target --}}
    <div class="col-12 col-lg-4">
        <label class="form-label fw-semibold">Target</label>
        <select name="target" id="wa-target" class="form-select" required>
            <option value="">-- Select Target --</option>
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>All students</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
            <option value="specific_students" {{ old('target')==='specific_students' ? 'selected' : '' }}>Select Specific Students</option>
            <option value="custom" {{ old('target')==='custom' ? 'selected' : '' }}>Custom numbers</option>
        </select>
        <small class="text-muted d-block mt-1">Pulls WhatsApp fields first, then phone numbers.</small>
    </div>

    {{-- Schedule --}}
    <div class="col-12 col-lg-4 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label fw-semibold">Schedule</label>
            <div class="row g-2">
                <div class="col-6">
                    <select name="schedule" id="wa-schedule" class="form-select">
                        <option value="now" {{ old('schedule')==='now' ? 'selected' : '' }}>Send now</option>
                        <option value="later" {{ old('schedule')==='later' ? 'selected' : '' }}>Schedule</option>
                    </select>
                </div>
                <div class="col-6 wa-schedule-later-field d-none">
                    <input type="datetime-local" name="send_at" class="form-control" value="{{ old('send_at') }}">
                </div>
            </div>
            <small class="text-muted d-block mt-1">Pick a time if scheduling.</small>
        </div>
    </div>

    {{-- Target detail --}}
    <div class="col-12 col-lg-6 wa-target-field wa-target-class d-none">
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
    <div class="col-12 col-lg-6 wa-target-field wa-target-student d-none">
        <label class="form-label fw-semibold">Student</label>
        <select name="student_id" class="form-select">
            <option value="">-- Select Student --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Uses the parent's WhatsApp numbers first, then phone numbers.</small>
    </div>
    <div class="col-12 wa-target-field wa-target-specific_students d-none">
        <label class="form-label fw-semibold">Select Multiple Students</label>
        <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
            <i class="bi bi-people-fill"></i> Open Student Selector
        </button>
        <input type="hidden" name="selected_student_ids" id="waSelectedStudentIds" value="{{ old('selected_student_ids') }}">
        <div id="waSelectedStudentsDisplay" class="mt-2 d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted fw-semibold">Selected Students:</small>
                <span class="badge bg-success" id="waSelectedStudentsBadge">0</span>
            </div>
            <div id="waSelectedStudentsList" class="d-flex flex-wrap gap-2"></div>
        </div>
        <small class="text-muted d-block mt-1">Click to search and select multiple students. WhatsApp will be sent to parents of selected students.</small>
    </div>
    <div class="col-12 wa-target-field wa-target-custom d-none">
        <label class="form-label fw-semibold">Custom Numbers</label>
        <textarea name="custom_numbers" class="form-control" rows="3" placeholder="2547..., 2547...">{{ old('custom_numbers') }}</textarea>
        <small class="text-muted d-block mt-1">Use full country code; plus sign is optional. Use this to send to guardians by entering their numbers manually.</small>
    </div>

    @include('communication.partials.fee-balance-exclude-filters')
    @include('communication.partials.exclude-student-modal')

    {{-- Body --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Message</label>
        <textarea name="message" rows="6" class="form-control" id="wa-body" placeholder="Type the WhatsApp message to send.">{{ old('message') }}</textarea>
        <div class="d-flex justify-content-between text-muted small mt-1">
            <span>Placeholders allowed (see below).</span>
            <span id="wa-counter">0 chars</span>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Media (photo/video)</label>
        <input type="file" name="media" id="wa-media" class="form-control" accept="image/*,video/*">
        <small class="text-muted d-block">Media is sent as a link; scheduling with media is disabled.</small>
        <div id="wa-media-preview" class="mt-2 d-none"></div>
    </div>

    <div class="col-12">
        <div class="card bg-light p-3 mb-3">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="use_queue" id="use_queue" value="1" checked>
                <label class="form-check-label" for="use_queue">
                    <strong>Use Queue Mode (Recommended for 10+ recipients)</strong>
                </label>
                <small class="text-muted d-block ms-4">Processes in background, handles failures better, can resume if interrupted.</small>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skip_sent" id="skip_sent" value="1" checked>
                <label class="form-check-label" for="skip_sent">
                    <strong>Skip Already Sent Messages</strong>
                </label>
                <small class="text-muted d-block ms-4">Prevents sending duplicate messages to recipients who already received this message in the last 24 hours.</small>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="button" class="btn btn-outline-success" id="preview-whatsapp-btn" style="min-width: 120px;">
            <i class="bi bi-eye"></i> Preview
        </button>
        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-whatsapp"></i> Send WhatsApp</button>
    </div>
</form>

<div class="settings-card mt-4">
    @include('communication.templates.partials.placeholder-selector', [
        'systemPlaceholders' => $systemPlaceholders ?? [],
        'customPlaceholders' => $customPlaceholders ?? collect(),
        'targetField' => 'wa-body'
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
    const modeTemplate = document.getElementById('wa_mode_template');
    const modeManual = document.getElementById('wa_mode_manual');
    const modeBlocks = {
        template: document.querySelectorAll('.wa-mode-template'),
        manual: document.querySelectorAll('.wa-mode-manual')
    };
    const targetSelect = document.getElementById('wa-target');
    const scheduleSelect = document.getElementById('wa-schedule');
    const targetFields = {
        class: document.querySelectorAll('.wa-target-class'),
        student: document.querySelectorAll('.wa-target-student'),
        specific_students: document.querySelectorAll('.wa-target-specific_students'),
        custom: document.querySelectorAll('.wa-target-custom')
    };
    const scheduleLater = document.querySelectorAll('.wa-schedule-later-field');
    const body = document.getElementById('wa-body');
    const counter = document.getElementById('wa-counter');
    const mediaInput = document.getElementById('wa-media');
    const mediaPreview = document.getElementById('wa-media-preview');

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
        if (!body || !counter) return;
        counter.innerText = `${body.value.length} chars`;
    }

    if (modeTemplate && modeManual) {
        modeTemplate.addEventListener('change', refreshMode);
        modeManual.addEventListener('change', refreshMode);
        refreshMode();
    }
    if (targetSelect) { targetSelect.addEventListener('change', refreshTargetFields); refreshTargetFields(); }
    if (scheduleSelect) { scheduleSelect.addEventListener('change', refreshScheduleFields); refreshScheduleFields(); }
    if (body) { body.addEventListener('input', updateCounter); updateCounter(); }

    // Placeholder click -> insert at caret (preferred wa body) and keep copy fallback
    const waForm = document.querySelector('form[action="{{ route('communication.send.whatsapp.submit') }}"]');
    const placeholderButtons = document.querySelectorAll('.placeholder-chip');

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
        if (body) return body;
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
            btn.classList.add('btn-success','text-white');
            setTimeout(() => btn.classList.remove('btn-success','text-white'), 600);
        });
    });

    document.querySelectorAll('.emoji-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.value;
            const targetField = getPreferredField();
            insertAtCursor(targetField, val);
        });
    });

    if (mediaInput && mediaPreview) {
        mediaInput.addEventListener('change', () => {
            mediaPreview.innerHTML = '';
            const file = mediaInput.files?.[0];
            if (!file) {
                mediaPreview.classList.add('d-none');
                return;
            }
            const url = URL.createObjectURL(file);
            let node = null;
            if (file.type.startsWith('image/')) {
                node = document.createElement('img');
                node.src = url;
                node.style.maxWidth = '180px';
                node.className = 'img-thumbnail';
            } else if (file.type.startsWith('video/')) {
                node = document.createElement('video');
                node.src = url;
                node.controls = true;
                node.style.maxWidth = '220px';
            }
            if (node) {
                mediaPreview.appendChild(node);
                mediaPreview.classList.remove('d-none');
            }
        });
    }

    // Smart placeholder validation before submit
    // Use escaped Blade syntax so these remain literal placeholders
    const studentPlaceholders = ['@{{student_name}}', '@{{class_name}}', '@{{parent_name}}'];
    const genericPlaceholders = ['@{{school_name}}', '@{{date}}'];

    function validatePlaceholders(evt) {
        if (!waForm) return;
        const target = targetSelect?.value;
        if (!target) return;

        const messageText = body?.value || '';
        const used = placeholderButtons
            ? Array.from(placeholderButtons).map(b => b.dataset.value).filter(ph => messageText.includes(ph))
            : [];

        if (!used.length) return;

        const usesStudentData = used.some(ph => studentPlaceholders.includes(ph));
        const onlyGeneric = used.every(ph => genericPlaceholders.includes(ph));

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

        if (usesStudentData && (target === 'staff' || target === 'custom')) {
            evt.preventDefault();
            alert('Student/parent/class placeholders need a student/parent/class target, not staff/custom.');
            return;
        }

        if (target === 'custom' && !onlyGeneric) {
            evt.preventDefault();
            alert('Custom numbers can only use @{{school_name}} or @{{date}} placeholders.');
            return;
        }
    }

    if (waForm) {
        waForm.addEventListener('submit', validatePlaceholders);
    }

    // Handle student selection from modal for WhatsApp
    const waSelectedStudentIdsInput = document.getElementById('waSelectedStudentIds');
    const waSelectedStudentsDisplay = document.getElementById('waSelectedStudentsDisplay');
    const waSelectedStudentsList = document.getElementById('waSelectedStudentsList');
    const waSelectedStudentsBadge = document.getElementById('waSelectedStudentsBadge');

    document.addEventListener('studentsSelected', function(event) {
        const studentIds = event.detail.studentIds;
        
        if (waSelectedStudentIdsInput) {
            waSelectedStudentIdsInput.value = studentIds.join(',');
        }
        
        // Update display
        if (studentIds.length > 0 && waSelectedStudentsDisplay) {
            waSelectedStudentsDisplay.classList.remove('d-none');
            waSelectedStudentsBadge.textContent = studentIds.length;
            
            // Show student names
            waSelectedStudentsList.innerHTML = '';
            studentIds.forEach(id => {
                const checkbox = document.querySelector(`#student_${id}`);
                if (checkbox) {
                    const label = checkbox.closest('.student-item');
                    const studentName = label.querySelector('.fw-semibold')?.textContent.trim() || `Student ${id}`;
                    
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-success';
                    badge.textContent = studentName;
                    waSelectedStudentsList.appendChild(badge);
                }
            });
        } else if (waSelectedStudentsDisplay) {
            waSelectedStudentsDisplay.classList.add('d-none');
        }
    });

    // Preview functionality for WhatsApp
    const previewWhatsAppBtn = document.getElementById('preview-whatsapp-btn');
    if (previewWhatsAppBtn) {
        previewWhatsAppBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = waForm;
            if (!form) {
                alert('Form not found. Please refresh the page.');
                return;
            }
            
            const formData = new FormData(form);
            
            // Get message from textarea (works for both template and manual mode)
            const messageTextarea = document.getElementById('wa-body');
            let message = messageTextarea?.value || '';
            
            // If no message in textarea and template is selected, try to get from template
            if (!message) {
                const templateId = formData.get('template_id');
                if (templateId) {
                    // For now, just alert - in a real scenario you'd fetch template content via AJAX
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
            channelInput.value = 'whatsapp';
            previewForm.appendChild(channelInput);

            // Add form fields
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
});
</script>
@endpush