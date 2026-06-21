<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $page->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug ?? '') }}" placeholder="auto-generated if empty">
    </div>
    <div class="col-md-8">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $page->title ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['draft', 'published', 'archived'] as $status)
                <option value="{{ $status }}" @selected(old('status', $page->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Meta Title</label>
        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Published At</label>
        <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($page->published_at ?? null)?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Meta Description</label>
        <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_homepage" value="1" id="is_homepage" @checked(old('is_homepage', $page->is_homepage ?? false))>
            <label class="form-check-label" for="is_homepage">Set as homepage</label>
        </div>
    </div>
</div>
