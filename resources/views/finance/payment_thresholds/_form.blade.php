@php
    /** @var \App\Models\PaymentThreshold|null $threshold */
    $threshold = $threshold ?? null;
    $defaultTermId = $defaultTermId ?? null;
    $multiCategory = $multiCategory ?? false;
    $termIdValue = old('term_id', $threshold?->term_id ?? $defaultTermId);
    $oldCategoryIds = old('student_category_ids', []);
    if (! is_array($oldCategoryIds)) {
        $oldCategoryIds = [];
    }
    $oldCategoryIds = array_map('intval', $oldCategoryIds);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="finance-form-label">Term <span class="text-danger">*</span></label>
        <select name="term_id" class="finance-form-select @error('term_id') is-invalid @enderror" required>
            <option value="">— Select term —</option>
            @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ (int) $termIdValue === (int) $t->id ? 'selected' : '' }}>
                    {{ $t->name }}@if($t->academicYear) ({{ $t->academicYear->year }})@endif
                </option>
            @endforeach
        </select>
        @error('term_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        @if($multiCategory)
            <label class="finance-form-label">Student categories <span class="text-danger">*</span></label>
            <div class="border rounded p-2 @error('student_category_ids') border-danger @enderror @error('student_category_ids.*') border-danger @enderror" style="max-height: 220px; overflow-y: auto; background: var(--fin-surface-alt, #f9fafb);">
                @forelse($categories as $cat)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="student_category_ids[]" value="{{ $cat->id }}"
                               id="student_cat_{{ $cat->id }}" {{ in_array((int) $cat->id, $oldCategoryIds, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="student_cat_{{ $cat->id }}">{{ $cat->name }}</label>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No categories.</p>
                @endforelse
            </div>
            <small class="form-text d-block mt-1" style="color: var(--fin-muted, #6b7280);">Select one or more; each category gets its own row with the same percentages and deadlines.</small>
            @error('student_category_ids')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @error('student_category_ids.*')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        @else
            <label class="finance-form-label">Student category <span class="text-danger">*</span></label>
            <select name="student_category_id" class="finance-form-select @error('student_category_id') is-invalid @enderror" required>
                <option value="">— Select category —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (int) old('student_category_id', $threshold?->student_category_id) === (int) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('student_category_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        @endif
        @if($categories->isEmpty())
            <div class="alert alert-warning mt-2 mb-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                No student categories found.
                <a href="{{ route('student-categories.index') }}" class="alert-link">Manage student categories</a>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <label class="finance-form-label">Minimum % paid <span class="text-danger">*</span></label>
        <input type="number" name="minimum_percentage" step="0.01" min="0" max="100"
               class="finance-form-control @error('minimum_percentage') is-invalid @enderror"
               value="{{ old('minimum_percentage', $threshold?->minimum_percentage) }}" required>
        <small class="form-text" style="color: var(--fin-muted, #6b7280);">Required share of term fees to count as cleared (before deadline rules).</small>
        @error('minimum_percentage')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="finance-form-label">Final deadline — day of month <span class="text-danger">*</span></label>
        <input type="number" name="final_deadline_day" min="1" max="31"
               class="finance-form-control @error('final_deadline_day') is-invalid @enderror"
               value="{{ old('final_deadline_day', $threshold?->final_deadline_day ?? 5) }}" required>
        @error('final_deadline_day')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="finance-form-label">Months after term opening <span class="text-danger">*</span></label>
        <input type="number" name="final_deadline_month_offset" min="0" max="36"
               class="finance-form-control @error('final_deadline_month_offset') is-invalid @enderror"
               value="{{ old('final_deadline_month_offset', $threshold?->final_deadline_month_offset ?? 2) }}" required>
        <small class="form-text" style="color: var(--fin-muted, #6b7280);">e.g. 2 = second month after opening; combined with day above for clearance deadline.</small>
        @error('final_deadline_month_offset')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12">
        <label class="finance-form-label">Notes</label>
        <textarea name="notes" class="finance-form-control" rows="2" maxlength="2000">{{ old('notes', $threshold?->notes) }}</textarea>
    </div>

    <div class="col-md-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', $threshold?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
            <small class="form-text d-block" style="color: var(--fin-muted, #6b7280);">Inactive thresholds are ignored when computing fee clearance.</small>
        </div>
    </div>
</div>
