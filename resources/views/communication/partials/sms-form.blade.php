<div class="d-flex gap-2 mb-3 flex-wrap">
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sms_mode" id="sms_mode_template" value="template" checked>
        <label class="form-check-label fw-semibold" for="sms_mode_template">Use Template</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sms_mode" id="sms_mode_manual" value="manual">
        <label class="form-check-label fw-semibold" for="sms_mode_manual">Manual Compose</label>
    </div>
</div>

<form method="POST" action="{{ route('communication.send.sms.submit') }}" class="row g-3">
    @csrf
    <div class="col-md-4 sms-mode sms-mode-template">
        <label class="form-label fw-semibold">Template</label>
        <select name="template_id" class="form-select">
            <option value="">-- None --</option>
            @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>
                    {{ $tpl->title }} ({{ strtoupper($tpl->type) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Optional. Loads content when chosen.</small>
    </div>
    <div class="col-md-4 sms-mode sms-mode-template d-none"></div>
    <input type="hidden" name="sender_id" value="{{ env('SMS_SENDER_ID', config('app.name')) }}">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Target</label>
        <select name="target" id="sms-target" class="form-select" required>
            <option value="">-- Select Target --</option>
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>Parents (all)</option>
            <option value="students" {{ old('target')==='students' ? 'selected' : '' }}>Students (all)</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
            <option value="custom" {{ old('target')==='custom' ? 'selected' : '' }}>Custom numbers</option>
        </select>
    </div>

    <div class="col-md-6 sms-target-field sms-target-class d-none">
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
    <div class="col-md-6 sms-target-field sms-target-student d-none">
        <label class="form-label fw-semibold">Student</label>
        <select name="student_id" class="form-select">
            <option value="">-- Select Student --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name ?? ($student->first_name.' '.$student->last_name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Sends to the selected student's parent contacts.</small>
    </div>
    <div class="col-md-6 sms-target-field sms-target-custom d-none">
        <label class="form-label fw-semibold">Custom Numbers</label>
        <textarea name="custom_numbers" class="form-control" rows="3" placeholder="+2547..., +2547...">{{ old('custom_numbers') }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Message</label>
        <textarea name="message" rows="5" class="form-control" placeholder="160 characters per SMS segment.">{{ old('message') }}</textarea>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Schedule</label>
        <select name="schedule" id="sms-schedule" class="form-select">
            <option value="now" {{ old('schedule')==='now' ? 'selected' : '' }}>Send now</option>
            <option value="later" {{ old('schedule')==='later' ? 'selected' : '' }}>Schedule for later</option>
        </select>
    </div>
    <div class="col-md-3 sms-schedule-later-field d-none">
        <label class="form-label fw-semibold">Send At</label>
        <input type="datetime-local" name="send_at" class="form-control" value="{{ old('send_at') }}">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary"><i class="bi bi-send"></i> Send SMS</button>
    </div>
</form>

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
        custom: document.querySelectorAll('.sms-target-custom')
    };
    const scheduleLater = document.querySelectorAll('.sms-schedule-later-field');

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

    if (modeTemplate && modeManual) {
        modeTemplate.addEventListener('change', refreshMode);
        modeManual.addEventListener('change', refreshMode);
        refreshMode();
    }
    if (targetSelect) { targetSelect.addEventListener('change', refreshTargetFields); refreshTargetFields(); }
    if (scheduleSelect) { scheduleSelect.addEventListener('change', refreshScheduleFields); refreshScheduleFields(); }
});
</script>
@endpush

