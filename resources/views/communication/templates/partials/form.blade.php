<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Code</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $template->code ?? '') }}" required>
        <small class="text-muted">Unique identifier (e.g. ADMISSION_WELCOME)</small>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Title</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $template->title ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Type</label>
        <select name="type" class="form-select" required>
            @php $currentType = old('type', $template->type ?? 'email'); @endphp
            <option value="email" {{ $currentType === 'email' ? 'selected' : '' }}>Email</option>
            <option value="sms" {{ $currentType === 'sms' ? 'selected' : '' }}>SMS</option>
            <option value="whatsapp" {{ $currentType === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Subject (Email only)</label>
        <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject', $template->subject ?? '') }}" placeholder="Optional subject for email templates">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Attachment (optional)</label>
        <input type="file" name="attachment" class="form-control">
        @if(!empty($template?->attachment))
            <small class="text-muted d-block mt-1">Current: <a href="{{ asset('storage/'.$template->attachment) }}" target="_blank">View</a></small>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Content</label>
        <textarea name="content" id="content" rows="10" class="form-control" required>{{ old('content', $template->content ?? '') }}</textarea>
        <small class="text-muted">Use placeholders to personalize messages.</small>
    </div>

    <div class="col-12">
        @include('communication.templates.partials.placeholder-selector', [
            'systemPlaceholders' => $systemPlaceholders,
            'customPlaceholders' => $customPlaceholders ?? collect(),
            'targetField' => 'content'
        ])
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('communication-templates.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Save Template</button>
    </div>
</div>

