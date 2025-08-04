@csrf
<div class="mb-3">
    <label for="name">Name *</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $votehead->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="description">Description</label>
    <textarea name="description" class="form-control">{{ old('description', $votehead->description ?? '') }}</textarea>
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_mandatory" class="form-check-input"
        {{ old('is_mandatory', $votehead->is_mandatory ?? false) ? 'checked' : '' }}>
    <label class="form-check-label">Mandatory</label>
</div>

<button type="submit" class="btn btn-success">Save</button>
<a href="{{ route('voteheads.index') }}" class="btn btn-secondary">Cancel</a>
