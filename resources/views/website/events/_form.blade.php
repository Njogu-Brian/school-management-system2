<div class="row g-3">
<div class="col-md-6"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="{{ old('title', $event->title ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $event->slug ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($event->start_date ?? null)?->format('Y-m-d')) }}" required></div>
<div class="col-md-4"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($event->end_date ?? null)?->format('Y-m-d')) }}"></div>
<div class="col-md-4"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="{{ old('location', $event->location ?? '') }}"></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4">{{ old('description', $event->description ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">Cover Image</label><input type="file" name="cover_image" class="form-control"></div>
<div class="col-md-6"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="registration_enabled" value="1" @checked(old('registration_enabled', $event->registration_enabled ?? false))><label class="form-check-label">Registration Enabled</label></div></div>
</div>
