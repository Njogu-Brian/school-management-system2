@extends('layouts.app')
@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
        <strong>Success!</strong> {{ session('success') }}
        @if(can_access('communication', 'email', 'add'))
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
    <h4>📧 Send Email</h4>

    <form method="POST" action="{{ route('communication.send.email.submit') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="email">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="emailTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">Use Template</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Manual Compose</button>
            </li>
        </ul>

        <div class="tab-content" id="emailTabContent">
            <!-- Template Tab -->
            <div class="tab-pane fade show active" id="template" role="tabpanel">
                <div class="mb-3">
                    <label>Email Template</label>
                    <select name="template_id" class="form-select">
                        <option value="">-- Select Template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Manual Compose Tab -->
            <div class="tab-pane fade" id="manual" role="tabpanel">
                <div class="mb-3">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter email subject">
                </div>

                <div class="mb-3">
                    <label>Attachment</label>
                    <input type="file" name="attachment" class="form-control">
                </div>

                <div class="mb-3 position-relative">
                    <label>Message *</label>
                    <div class="d-flex align-items-center mb-2">
                        <select id="placeholder-select-email" class="form-select w-auto me-2" style="min-width:180px;">
                            <option value="">Insert Placeholder</option>
                            @foreach(available_placeholders() as $ph)
                                <option value="{{ $ph }}">{{ $ph }}</option>
                            @endforeach
                            @foreach(\App\Models\CommunicationPlaceholder::all() as $custom)
                                <option value="{{ '{'.$custom->key.'}' }}">{{ '{'.$custom->key.'}' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Use placeholders to personalize your message.</small>
                    </div>

                    <textarea id="message-area-email" name="message" rows="6" class="form-control"
                        placeholder="Write your email here. Use {student_name}, {parent_name}, {school_name}, etc."></textarea>
                </div>
            </div>
        </div>

        <!-- Target Group -->
        <div class="mb-3">
            <label>Target Group *</label>
            <select name="target" class="form-select" id="target-select-email" required>
                <option value="students">Students</option>
                <option value="parents">Parents</option>
                <option value="teachers">Teachers</option>
                <option value="staff">Staff</option>
                <option value="custom">Custom Recipients</option>
            </select>
        </div>

        <!-- Custom Emails -->
        <div class="mb-3 d-none" id="custom-emails-field">
            <label>Custom Recipient Emails <small>(comma-separated)</small></label>
            <textarea name="custom_emails" rows="3" class="form-control"
                      placeholder="example1@mail.com, example2@mail.com"></textarea>
        </div>

        <!-- Schedule -->
        <div class="mb-3">
            <label>Send Timing</label>
            <div>
                <label class="me-3"><input type="radio" name="schedule" value="now" checked> Send Now</label>
                <label><input type="radio" name="schedule" value="later"> Schedule</label>
            </div>
        </div>

        <button class="btn btn-primary"><i class="bi bi-send-check"></i> Send Email</button>
    </form>
</div>

<script>
document.getElementById('target-select-email').addEventListener('change', function () {
    const field = document.getElementById('custom-emails-field');
    field.classList.toggle('d-none', this.value !== 'custom');
});

document.getElementById('placeholder-select-email').addEventListener('change', function() {
    const val = this.value;
    if (!val) return;
    const area = document.getElementById('message-area-email');
    const start = area.selectionStart;
    const end = area.selectionEnd;
    area.value = area.value.slice(0, start) + val + area.value.slice(end);
    area.focus();
    area.selectionStart = area.selectionEnd = start + val.length;
    this.selectedIndex = 0;
});
</script>
@endsection
