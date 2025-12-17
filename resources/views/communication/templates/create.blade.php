@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Create Communication Template',
        'icon' => 'bi bi-file-plus',
        'subtitle' => 'Create a new SMS or email template for automated communications',
        'actions' => '<a href="' . route('communication-templates.index') . '" class="btn btn-comm btn-comm-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <form action="{{ route('communication-templates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="comm-form-label">Template Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control comm-form-control" placeholder="e.g. welcome_staff" required>
                    <small class="text-muted">Unique identifier used across modules (attendance, admissions, finance, etc.)</small>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control comm-form-control" required>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select comm-form-control" required>
                        <option value="email" {{ old('type')==='email'?'selected':'' }}>Email</option>
                        <option value="sms" {{ old('type')==='sms'?'selected':'' }}>SMS</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Subject (Email)</label>
                    <div class="input-group">
                        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" class="form-control comm-form-control">
                        <button type="button" class="btn btn-comm-outline" data-bs-toggle="collapse" data-bs-target="#placeholderSelectorSubject" aria-expanded="false">
                            <i class="bi bi-tag me-1"></i> Insert Placeholder
                        </button>
                    </div>
                    <div class="collapse mt-2" id="placeholderSelectorSubject">
                        @include('communication.templates.partials.placeholder-selector', [
                            'targetField' => 'subject',
                            'systemPlaceholders' => $systemPlaceholders ?? [],
                            'customPlaceholders' => $customPlaceholders ?? collect()
                        ])
                    </div>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Attachment (Email)</label>
                    <input type="file" name="attachment" class="form-control comm-form-control">
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Message <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-comm-outline" data-bs-toggle="collapse" data-bs-target="#placeholderSelectorContent" aria-expanded="false">
                            <i class="bi bi-tag me-1"></i> Insert Placeholder
                        </button>
                    </div>
                    <textarea name="content" id="content" rows="10" class="form-control comm-form-control rich-text" required>{{ old('content') }}</textarea>
                    <div class="collapse mt-2" id="placeholderSelectorContent">
                        @include('communication.templates.partials.placeholder-selector', [
                            'targetField' => 'content',
                            'systemPlaceholders' => $systemPlaceholders ?? [],
                            'customPlaceholders' => $customPlaceholders ?? collect()
                        ])
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-comm btn-comm-primary">
                        <i class="bi bi-check-circle me-2"></i> Save Template
                    </button>
                    <a href="{{ route('communication-templates.index') }}" class="btn btn-comm btn-comm-outline">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
