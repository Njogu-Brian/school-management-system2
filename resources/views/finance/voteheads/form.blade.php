<div class="row g-3">
    <div class="col-md-12">
        <label for="name" class="finance-form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="finance-form-control" value="{{ old('name', $votehead->name ?? '') }}" required>
    </div>

    <div class="col-md-12">
        <label for="description" class="finance-form-label">Description</label>
        <textarea name="description" class="finance-form-control" rows="3">{{ old('description', $votehead->description ?? '') }}</textarea>
    </div>

    <div class="col-md-12">
        <div class="form-check">
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
    </div>

    <div class="col-md-12">
        <label for="charge_type" class="finance-form-label">Charge Type <span class="text-danger">*</span></label>
        <select name="charge_type" class="finance-form-select" id="charge_type" required>
            <option value="">-- Select Charge Type --</option>
            <option value="per_student" {{ old('charge_type', $votehead->charge_type ?? '') === 'per_student' ? 'selected' : '' }}>Per Student (Each Term)</option>
            <option value="once" {{ old('charge_type', $votehead->charge_type ?? '') === 'once' ? 'selected' : '' }}>Once Only</option>
            <option value="once_annually" {{ old('charge_type', $votehead->charge_type ?? '') === 'once_annually' ? 'selected' : '' }}>Once Annually</option>
            <option value="per_family" {{ old('charge_type', $votehead->charge_type ?? '') === 'per_family' ? 'selected' : '' }}>Once per Family</option>
        </select>
    </div>

    <div class="col-md-12" id="preferred_term_wrapper" style="display: none;">
        <label for="preferred_term" class="finance-form-label">Preferred Term (for Once Annually fees)</label>
        <select name="preferred_term" class="finance-form-select" id="preferred_term">
            <option value="">-- No preference (charge in any term) --</option>
            <option value="1" {{ old('preferred_term', $votehead->preferred_term ?? '') == '1' ? 'selected' : '' }}>Term 1</option>
            <option value="2" {{ old('preferred_term', $votehead->preferred_term ?? '') == '2' ? 'selected' : '' }}>Term 2</option>
            <option value="3" {{ old('preferred_term', $votehead->preferred_term ?? '') == '3' ? 'selected' : '' }}>Term 3</option>
        </select>
        <small class="text-muted">If set, this fee will only be charged in the selected term (e.g., textbook fee in Term 1). Students joining later will still be charged in the preferred term.</small>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-finance btn-finance-success">
        <i class="bi bi-check-circle"></i> Save
    </button>
    <a href="{{ route('finance.voteheads.index') }}" class="btn btn-finance btn-finance-outline">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chargeTypeSelect = document.getElementById('charge_type');
        const preferredTermWrapper = document.getElementById('preferred_term_wrapper');
        
        function togglePreferredTerm() {
            if (chargeTypeSelect.value === 'once_annually') {
                preferredTermWrapper.style.display = 'block';
            } else {
                preferredTermWrapper.style.display = 'none';
            }
        }
        
        chargeTypeSelect.addEventListener('change', togglePreferredTerm);
        togglePreferredTerm(); // Initial check
    });
</script>
