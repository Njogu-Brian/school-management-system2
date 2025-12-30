<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-whatsapp fs-5"></i>
    <div>
        <div class="fw-semibold">WhatsApp-style compose</div>
        <small class="text-muted">Templates plus personalized placeholders; falls back to phone if no WhatsApp field.</small>
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
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>Parents (all)</option>
            <option value="students" {{ old('target')==='students' ? 'selected' : '' }}>Students (all)</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
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
                    {{ $student->full_name ?? ($student->first_name.' '.$student->last_name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">Uses the parent's WhatsApp numbers first, then phone numbers.</small>
    </div>
    <div class="col-12 wa-target-field wa-target-custom d-none">
        <label class="form-label fw-semibold">Custom Numbers</label>
        <textarea name="custom_numbers" class="form-control" rows="3" placeholder="2547..., 2547...">{{ old('custom_numbers') }}</textarea>
        <small class="text-muted d-block mt-1">Use full country code; plus sign is optional.</small>
    </div>

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

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary"><i class="bi bi-whatsapp"></i> Send WhatsApp</button>
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
});
</script>
@endpush