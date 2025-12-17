@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Send SMS',
        'icon' => 'bi bi-chat-dots',
        'subtitle' => 'Send SMS messages to students, parents, or staff',
        'actions' => ''
    ])

    @if(session('success'))
        <div class="alert alert-success comm-alert alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger comm-alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <form method="POST" action="{{ route('communication.send.sms.submit') }}">
                @csrf
                <input type="hidden" name="type" value="sms">

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="smsTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">
                            <i class="bi bi-file-text me-2"></i> Use Template
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                            <i class="bi bi-pencil me-2"></i> Manual Compose
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="smsTabContent">
                    <!-- Template -->
                    <div class="tab-pane fade show active" id="template" role="tabpanel">
                        <div class="mb-3">
                            <label class="comm-form-label">SMS Template</label>
                            <select name="template_id" class="form-select comm-form-control">
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
                            <label class="comm-form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" rows="5" maxlength="300" class="form-control comm-form-control" placeholder="Write your SMS message..."></textarea>
                            <small class="text-muted">Max 300 characters.</small>
                        </div>
                    </div>
                </div>

                <!-- Target -->
                <div class="mb-3 mt-4">
                    <label class="comm-form-label">Target <span class="text-danger">*</span></label>
                    <select name="target" id="target" class="form-select comm-form-control" required>
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
                    <label class="comm-form-label">Send Timing</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="schedule" id="scheduleNow" value="now" checked>
                        <label class="form-check-label" for="scheduleNow">Send Now</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="schedule" id="scheduleLater" value="later">
                        <label class="form-check-label" for="scheduleLater">Schedule for Later</label>
                    </div>
                    <div class="mt-2 d-none" id="scheduleTime">
                        <label class="comm-form-label">Send At (Date & Time)</label>
                        <input type="datetime-local" name="send_at" class="form-control comm-form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-comm btn-comm-success">
                    <i class="bi bi-send me-2"></i> Send SMS
                </button>
            </form>
        </div>
    </div>

    <!-- Placeholder Reference -->
    <div class="comm-card comm-animate">
        <div class="comm-card-header">
            <i class="bi bi-tags me-2"></i> Available Placeholders
        </div>
        <div class="comm-card-body">
            <p class="text-muted mb-3">Use these placeholders in your templates or manual messages. They will be replaced dynamically when sending.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Placeholder</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>{{school_name}}</code></td><td>School name</td></tr>
                        <tr><td><code>{{student_name}}</code></td><td>Student full name</td></tr>
                        <tr><td><code>{{parent_name}}</code></td><td>Parent or guardian name</td></tr>
                        <tr><td><code>{{class_name}}</code></td><td>Class or grade name</td></tr>
                        <tr><td><code>{{staff_name}}</code></td><td>Staff name</td></tr>
                        <tr><td><code>{{role}}</code></td><td>Staff role</td></tr>
                        <tr><td><code>{{date}}</code></td><td>Current date</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('target').addEventListener('change', function() {
    const extra = document.getElementById('targetExtra');
    extra.innerHTML = '';
    const val = this.value;

    if (val === 'student') {
        extra.innerHTML = `
            <div class="mb-3">
                <label class="comm-form-label">Select Student</label>
                <select name="student_id" class="form-select comm-form-control">
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
                <label class="comm-form-label">Select Class</label>
                <select name="classroom_id" class="form-select comm-form-control">
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
                <label class="comm-form-label">Custom Phone Numbers <small class="text-muted">(comma separated)</small></label>
                <textarea name="custom_numbers" rows="3" class="form-control comm-form-control"
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
