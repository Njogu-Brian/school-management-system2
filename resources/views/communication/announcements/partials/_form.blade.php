@csrf

<div class="mb-3">
    <label>Title</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title ?? '') }}" required>
</div>

<div class="mb-3">
    <label>Content</label>
    <textarea name="content" class="form-control" rows="4" required>{{ old('content', $announcement->content ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label>Status</label>
    <select name="active" class="form-select">
        <option value="1" {{ (old('active', $announcement->active ?? 1) == 1) ? 'selected' : '' }}>Active</option>
        <option value="0" {{ (old('active', $announcement->active ?? 1) == 0) ? 'selected' : '' }}>Inactive</option>
    </select>
</div>

<div class="mb-3">
    <label>Expires At (optional)</label>
    <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', isset($announcement->expires_at) ? $announcement->expires_at->format('Y-m-d') : '') }}">
</div>

<button type="submit" class="btn btn-primary">
    {{ isset($announcement) ? 'Update' : 'Create' }} Announcement
</button>
