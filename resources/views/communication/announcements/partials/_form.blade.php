@csrf

<div class="mb-3">
    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title ?? '') }}" required>
    @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
    <textarea name="content" class="form-control" rows="6" required>{{ old('content', $announcement->content ?? '') }}</textarea>
    <small class="text-muted">Enter the announcement message content</small>
    @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="active" class="form-select">
            <option value="1" {{ (old('active', $announcement->active ?? 1) == 1) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ (old('active', $announcement->active ?? 1) == 0) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Expires At <small class="text-muted">(optional)</small></label>
        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', isset($announcement->expires_at) ? $announcement->expires_at->format('Y-m-d') : '') }}">
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-settings-primary">
        <i class="bi bi-check-circle me-2"></i>
        {{ isset($announcement) ? 'Update' : 'Create' }} Announcement
    </button>
    <a href="{{ route('announcements.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-x-circle me-2"></i> Cancel
    </a>
</div>
