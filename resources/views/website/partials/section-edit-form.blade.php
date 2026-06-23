<details class="mt-3 border-top pt-3">
    <summary class="small text-primary cursor-pointer">Edit section content</summary>
    <form action="{{ $updateRoute }}" method="POST" class="mt-3 row g-2">
        @csrf
        @method('PUT')
        <div class="col-md-6">
            <label class="form-label small">Title</label>
            <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $section->title) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Subtitle</label>
            <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ old('subtitle', $section->subtitle) }}">
        </div>
        <div class="col-12">
            <label class="form-label small">Content (paragraphs — separate with blank line)</label>
            <textarea name="content" class="form-control form-control-sm" rows="4">{{ old('content', $section->content) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small">Settings (JSON) — image_url, href, label, items, photos, variant, bank, mpesa…</label>
            <textarea name="settings" class="form-control form-control-sm font-monospace" rows="5">{{ old('settings', json_encode($section->settings ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Sort order</label>
            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $section->sort_order) }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $section->id }}" @checked($section->is_active)>
                <label class="form-check-label small" for="active-{{ $section->id }}">Active</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-sm btn-settings-primary">Save Section</button>
        </div>
    </form>
    <p class="text-muted small mt-2 mb-0">
        <strong>Block types:</strong>
        page_hero, rich_text, stats, card_grid, info_grid, photo_grid, school_story, payment_methods, list_columns, cta_banner, editorial_intro, social_cta
    </p>
</details>
