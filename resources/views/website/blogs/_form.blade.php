<div class="row g-3">
<div class="col-md-8"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="{{ old('title', $blog->title ?? '') }}" required></div>
<div class="col-md-4"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $blog->slug ?? '') }}"></div>
<div class="col-12"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea></div>
<div class="col-12"><label class="form-label">Body</label><textarea name="body" class="form-control" rows="10" required>{{ old('body', $blog->body ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">Featured Image</label><input type="file" name="featured_image" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Published At</label><input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($blog->published_at ?? null)?->format('Y-m-d\TH:i')) }}"></div>
<div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="published" value="1" @checked(old('published', $blog->published ?? false))><label class="form-check-label">Published</label></div></div>
</div>
