<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $behaviour->name ?? '') }}" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Type <span class="text-danger">*</span></label>
    <select name="type" class="form-select" required>
      <option value="">-- Select Type --</option>
      <option value="positive" @selected(old('type', $behaviour->type ?? '') == 'positive')>Positive</option>
      <option value="negative" @selected(old('type', $behaviour->type ?? '') == 'negative')>Negative</option>
    </select>
  </div>
</div>
<div class="mt-3">
  <label class="form-label">Description</label>
  <textarea name="description" class="form-control" rows="3">{{ old('description', $behaviour->description ?? '') }}</textarea>
</div>
