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
    {{-- Always include hidden input to ensure value is sent even if checkbox is not checked --}}
    <input type="hidden" name="is_mandatory" value="0">
    <input
        type="checkbox"
        name="is_mandatory"
        value="1"
        class="form-check-input"
        id="is_mandatory"
        {{ old('is_mandatory', $votehead->is_mandatory ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_mandatory">Mandatory</label>
</div>

<div class="mb-3">
    <label for="charge_type">Charge Type *</label>
    <select name="charge_type" class="form-control" required>
        <option value="">-- Select Charge Type --</option>
        <option value="per_student" {{ old('charge_type', $votehead->charge_type ?? '') === 'per_student' ? 'selected' : '' }}>Per Student (Each Term)</option>
        <option value="once" {{ old('charge_type', $votehead->charge_type ?? '') === 'once' ? 'selected' : '' }}>Once Only</option>
        <option value="once_annually" {{ old('charge_type', $votehead->charge_type ?? '') === 'once_annually' ? 'selected' : '' }}>Once Annually</option>
        <option value="per_family" {{ old('charge_type', $votehead->charge_type ?? '') === 'per_family' ? 'selected' : '' }}>Once per Family</option>
    </select>
</div>

<button type="submit" class="btn btn-success">Save</button>
<a href="{{ route('voteheads.index') }}" class="btn btn-secondary">Cancel</a>
