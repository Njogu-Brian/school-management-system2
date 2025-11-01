@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4"><i class="bi bi-chat-dots"></i> Send SMS</h4>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('communication.send.sms.submit') }}">
        @csrf
        <input type="hidden" name="type" value="sms">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="smsTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">
                    <i class="bi bi-file-text"></i> Use Template
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                    <i class="bi bi-pencil"></i> Manual Compose
                </button>
            </li>
        </ul>

        <div class="tab-content" id="smsTabContent">
            <!-- Template -->
            <div class="tab-pane fade show active" id="template" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">SMS Template</label>
                    <select name="template_id" class="form-select">
                        <option value="">-- Select Template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Manual -->
            <div class="tab-pane fade" id="manual" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">Message *</label>
                    <textarea name="message" rows="5" maxlength="300" class="form-control" placeholder="Write your SMS message..."></textarea>
                    <small class="text-muted">Max 300 characters.</small>
                </div>
            </div>
        </div>

        <!-- Target -->
        <div class="mb-3 mt-4">
            <label class="form-label">Target *</label>
            <select name="target" id="target" class="form-select" required>
                <option value="">-- Select Target --</option>
                <option value="students">All Students</option>
                <option value="parents">All Parents</option>
                <option value="student">Specific Student</option>
                <option value="class">Class / Stream</option>
                <option value="staff">All Staff</option>
                <option value="custom">Custom Numbers</option>
            </select>
        </div>

        <div id="targetExtra"></div>

        <!-- Schedule -->
        <div class="mb-4 mt-3">
            <label class="form-label">Send Timing</label><br>
            <label class="me-3"><input type="radio" name="schedule" value="now" checked> Send Now</label>
            <label><input type="radio" name="schedule" value="later"> Schedule for Later</label>
            <div class="mt-2 d-none" id="scheduleTime">
                <label class="form-label">Send At (Date & Time)</label>
                <input type="datetime-local" name="send_at" class="form-control">
            </div>
        </div>

        <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Send SMS</button>
    </form>

    <!-- Placeholder Reference -->
    <hr class="my-4">
    <h5><i class="bi bi-tags"></i> Available Placeholders</h5>
    <table class="table table-sm table-bordered">
        <thead><tr><th>Placeholder</th><th>Description</th></tr></thead>
        <tbody>
            <tr><td>{school_name}</td><td>School name</td></tr>
            <tr><td>{student_name}</td><td>Student full name</td></tr>
            <tr><td>{parent_name}</td><td>Parent or guardian name</td></tr>
            <tr><td>{class_name}</td><td>Class or grade name</td></tr>
            <tr><td>{staff_name}</td><td>Staff name</td></tr>
            <tr><td>{role}</td><td>Staff role</td></tr>
            <tr><td>{date}</td><td>Current date</td></tr>
        </tbody>
    </table>
</div>

<script>
document.getElementById('target').addEventListener('change', function() {
    const extra = document.getElementById('targetExtra');
    extra.innerHTML = '';
    const val = this.value;

    if (val === 'student') {
        extra.innerHTML = `
            <div class="mb-3">
                <label>Select Student</label>
                <select name="student_id" class="form-select">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->name }} ({{ $s->admission_number }})
                        </option>
                    @endforeach
                </select>
            </div>
        `;
    }

    if (val === 'class') {
        extra.innerHTML = `
            <div class="mb-3">
                <label>Select Class</label>
                <select name="classroom_id" class="form-select">
                    @foreach(\App\Models\Academics\Classroom::orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} - {{ $c->section }}</option>
                    @endforeach
                </select>
            </div>
        `;
    }
    if (val === 'custom') {
        extra.innerHTML = `
            <div class="mb-3">
                <label>Custom Phone Numbers <small>(comma separated)</small></label>
                <textarea name="custom_numbers" rows="3" class="form-control"
                    placeholder="2547XXXXXXXX, 2547YYYYYYYY"></textarea>
            </div>
        `;
    }
});

document.querySelectorAll('input[name="schedule"]').forEach(el => {
    el.addEventListener('change', function() {
        document.getElementById('scheduleTime').classList.toggle('d-none', this.value !== 'later');
    });
});
</script>
@endsection
