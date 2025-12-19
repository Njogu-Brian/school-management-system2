@php
    $placeholders = [
        ['key' => '{{school_name}}',  'desc' => 'School name from settings'],
        ['key' => '{{date}}',         'desc' => 'Current date'],
        ['key' => '{{student_name}}', 'desc' => 'Student full name'],
        ['key' => '{{admission_no}}', 'desc' => 'Admission number'],
        ['key' => '{{class_name}}',   'desc' => 'Class / grade'],
        ['key' => '{{parent_name}}',  'desc' => 'Parent / guardian name'],
        ['key' => '{{staff_name}}',   'desc' => 'Staff full name'],
        ['key' => '{{role}}',         'desc' => 'Staff role / title'],
    ];
@endphp

<div class="d-flex gap-2 mb-3 flex-wrap">
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="email_mode" id="email_mode_template" value="template" checked>
        <label class="form-check-label fw-semibold" for="email_mode_template">Use Template</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="email_mode" id="email_mode_manual" value="manual">
        <label class="form-check-label fw-semibold" for="email_mode_manual">Manual Compose</label>
    </div>
</div>

<form method="POST" action="{{ route('communication.send.email.submit') }}" enctype="multipart/form-data" class="row g-3">
    @csrf

    {{-- Template mode --}}
    <div class="col-md-6 email-mode email-mode-template">
        <label class="form-label fw-semibold">Email Template</label>
        <select name="template_id" class="form-select">
            <option value="">-- Select Template --</option>
            @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>
                    {{ $tpl->title }} ({{ strtoupper($tpl->type) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Content/subject will load from the selected template.</small>
    </div>

    {{-- Manual mode --}}
    <div class="col-md-6 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Email Title *</label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Term Updates">
    </div>
    <div class="col-md-6 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Attachment (optional)</label>
        <input type="file" name="attachment" class="form-control">
    </div>
    <div class="col-12 email-mode email-mode-manual d-none">
        <label class="form-label fw-semibold">Message *</label>
        <textarea name="message" rows="6" class="form-control" placeholder="Write your message...">{{ old('message') }}</textarea>
    </div>

    {{-- Common targeting --}}
    <div class="col-md-6">
        <label class="form-label fw-semibold">Target *</label>
        <select name="target" id="email-target" class="form-select" required>
            <option value="">-- Select Target --</option>
            <option value="parents" {{ old('target')==='parents' ? 'selected' : '' }}>Parents (all)</option>
            <option value="students" {{ old('target')==='students' ? 'selected' : '' }}>Students (all)</option>
            <option value="staff" {{ old('target')==='staff' ? 'selected' : '' }}>Staff</option>
            <option value="class" {{ old('target')==='class' ? 'selected' : '' }}>Specific Class</option>
            <option value="student" {{ old('target')==='student' ? 'selected' : '' }}>Single Student (parents)</option>
            <option value="custom" {{ old('target')==='custom' ? 'selected' : '' }}>Custom email list</option>
        </select>
    </div>
    <div class="col-md-6 email-target-field email-target-class d-none">
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
    <div class="col-md-6 email-target-field email-target-student d-none">
        <label class="form-label fw-semibold">Student</label>
        <select name="student_id" class="form-select">
            <option value="">-- Select Student --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name ?? ($student->first_name.' '.$student->last_name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Sends to parents of the selected student.</small>
    </div>
    <div class="col-md-6 email-target-field email-target-custom d-none">
        <label class="form-label fw-semibold">Custom Emails</label>
        <textarea name="custom_emails" class="form-control" rows="3" placeholder="email1@example.com, email2@example.com">{{ old('custom_emails') }}</textarea>
    </div>

    {{-- Schedule --}}
    <div class="col-md-3">
        <label class="form-label fw-semibold">Send Timing</label>
        <select name="schedule" id="email-schedule" class="form-select">
            <option value="now" {{ old('schedule')==='now' ? 'selected' : '' }}>Send now</option>
            <option value="later" {{ old('schedule')==='later' ? 'selected' : '' }}>Schedule for later</option>
        </select>
    </div>
    <div class="col-md-3 email-schedule-later-field d-none">
        <label class="form-label fw-semibold">Send At</label>
        <input type="datetime-local" name="send_at" class="form-control" value="{{ old('send_at') }}">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary"><i class="bi bi-send"></i> Send Email</button>
    </div>
</form>

<div class="settings-card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Available Placeholders</h6>
        <span class="text-muted small">Use these inside templates or manual messages</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Placeholder</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($placeholders as $ph)
                        <tr>
                            <td class="text-primary">{{ $ph['key'] }}</td>
                            <td>{{ $ph['desc'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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

