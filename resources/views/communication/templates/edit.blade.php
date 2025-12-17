@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Edit Communication Template',
        'icon' => 'bi bi-pencil-square',
        'subtitle' => 'Update SMS or email template settings',
        'actions' => '<a href="' . route('communication-templates.index') . '" class="btn btn-comm btn-comm-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <form action="{{ route('communication-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="comm-form-label">Template Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code',$template->code) }}" class="form-control comm-form-control" required>
                    <small class="text-muted">Unique identifier used across modules.</small>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title',$template->title) }}" class="form-control comm-form-control" required>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select comm-form-control" required>
                        <option value="email" @selected($template->type==='email')>Email</option>
                        <option value="sms" @selected($template->type==='sms')>SMS</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Subject (Email)</label>
                    <div class="input-group">
                        <input type="text" name="subject" id="subject" value="{{ old('subject',$template->subject) }}" class="form-control comm-form-control">
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
                    @if($template->attachment)
                        <p class="mt-2">
                            <small class="text-muted">Current attachment: </small>
                            <a href="{{ asset('storage/'.$template->attachment) }}" target="_blank" class="btn btn-sm btn-comm-outline">
                                <i class="bi bi-paperclip me-1"></i> View
                            </a>
                        </p>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="comm-form-label">Message <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-comm-outline" data-bs-toggle="collapse" data-bs-target="#placeholderSelectorContent" aria-expanded="false">
                            <i class="bi bi-tag me-1"></i> Insert Placeholder
                        </button>
                    </div>
                    <textarea name="content" id="content" rows="10" class="form-control comm-form-control rich-text" required>{{ old('content',$template->content) }}</textarea>
                    <div class="collapse mt-2" id="placeholderSelectorContent">
                        @include('communication.templates.partials.placeholder-selector', [
                            'targetField' => 'content',
                            'systemPlaceholders' => $systemPlaceholders ?? [],
                            'customPlaceholders' => $customPlaceholders ?? collect()
                        ])
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-comm btn-comm-success">
                        <i class="bi bi-check-circle me-2"></i> Update Template
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
