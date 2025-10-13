@extends('layouts.app')
@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
        <strong>Success!</strong> {{ session('success') }}
        @if(can_access('communication', 'sms', 'add'))
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        @endif
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
        <strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="container">
    <h4>📲 Send SMS</h4>

    <form method="POST" action="{{ route('communication.send.sms.submit') }}">
        @csrf
        <input type="hidden" name="type" value="sms">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="smsTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">Use Template</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Manual Compose</button>
            </li>
        </ul>

        <div class="tab-content" id="smsTabContent">
            <!-- Template Tab -->
            <div class="tab-pane fade show active" id="template" role="tabpanel">
                <div class="mb-3">
                    <label>SMS Template</label>
                    <select name="template_id" class="form-select">
                        <option value="">-- Select Template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Manual Tab -->
            <div class="tab-pane fade" id="manual" role="tabpanel">
                <div class="mb-3 position-relative">
                    <label>Message *</label>
                    <div class="d-flex align-items-center mb-2">
                        <select id="placeholder-select-sms" class="form-select w-auto me-2" style="min-width:180px;">
                            <option value="">Insert Placeholder</option>
                            @foreach(available_placeholders() as $ph)
                                <option value="{{ $ph }}">{{ $ph }}</option>
                            @endforeach
                            @foreach(\App\Models\CommunicationPlaceholder::all() as $custom)
                                <option value="{{ '{'.$custom->key.'}' }}">{{ '{'.$custom->key.'}' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Insert personalization tags here.</small>
                    </div>

                    <textarea id="message-area-sms" name="message" rows="5" class="form-control"
                        maxlength="300"
                        placeholder="Type your SMS (max 300 chars). Use {student_name}, {parent_name}, {school_name}, etc."></textarea>
                </div>
            </div>
        </div>

        <!-- Target Group -->
        <div class="mb-3">
            <label>Target Group *</label>
            <select name="target" class="form-select" id="target-select-sms" required>
                <option value="students">Students</option>
                <option value="parents">Parents</option>
                <option value="teachers">Teachers</option>
                <option value="staff">Staff</option>
                <option value="custom">Custom Numbers</option>
            </select>
        </div>

        <!-- Custom Numbers -->
        <div class="mb-3 d-none" id="custom-numbers-field">
            <label>Custom Phone Numbers <small>(comma-separated)</small></label>
            <textarea name="custom_numbers" rows="3" class="form-control"
                      placeholder="2547XXXXXXXX, 2547YYYYYYYY"></textarea>
        </div>

        <!-- Schedule Option -->
        <div class="mb-3">
            <label>Send Timing</label>
            <div>
                <label class="me-3"><input type="radio" name="schedule" value="now" checked> Send Now</label>
                <label><input type="radio" name="schedule" value="later"> Schedule</label>
            </div>
        </div>

        <button class="btn btn-success"><i class="bi bi-send"></i> Send SMS</button>
    </form>
</div>

<script>
document.getElementById('target-select-sms').addEventListener('change', function () {
    const field = document.getElementById('custom-numbers-field');
    field.classList.toggle('d-none', this.value !== 'custom');
});

document.getElementById('placeholder-select-sms').addEventListener('change', function() {
    const val = this.value;
    if (!val) return;
    const area = document.getElementById('message-area-sms');
    const start = area.selectionStart;
    const end = area.selectionEnd;
    area.value = area.value.slice(0, start) + val + area.value.slice(end);
    area.focus();
    area.selectionStart = area.selectionEnd = start + val.length;
    this.selectedIndex = 0;
});
</script>
@endsection
