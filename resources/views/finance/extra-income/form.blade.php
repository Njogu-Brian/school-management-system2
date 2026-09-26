@php
    $item = $item ?? null;
    $selectedKind = old('kind', $item->kind ?? 'trip');
    $selectedClassroomIds = collect(old('classroom_ids', $item?->classroomIds() ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="row g-4">
    <div class="col-md-8">
        <label class="finance-form-label" for="name">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="finance-form-control" required maxlength="150"
            value="{{ old('name', $item->name ?? '') }}" placeholder="Grade 4 Nairobi trip">
    </div>
    <div class="col-md-4">
        <label class="finance-form-label" for="kind">Type <span class="text-danger">*</span></label>
        <select name="kind" id="kind" class="finance-form-select" required>
            @foreach($kinds as $value => $label)
                <option value="{{ $value }}" {{ $selectedKind === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="finance-form-label" for="classroom_ids">Classes <span class="text-danger" id="classRequiredMark">*</span></label>
        <select name="classroom_ids[]" id="classroom_ids" class="finance-form-select" multiple size="8">
            @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ in_array((string) $classroom->id, $selectedClassroomIds, true) ? 'selected' : '' }}>
                    {{ $classroom->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted" id="classHelp">Hold Ctrl (Windows) or Cmd (Mac) to select more than one class. Only students in the selected classes can be charged or split onto this activity.</small>
    </div>
    <div class="col-md-6">
        <label class="finance-form-label" for="amount">Amount per student <span class="text-danger">*</span></label>
        <input type="number" name="amount" id="amount" class="finance-form-control" required min="0.01" step="0.01"
            value="{{ old('amount', $item->amount ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="finance-form-label" for="academic_year_id">Academic year <span class="text-danger">*</span></label>
        <select name="academic_year_id" id="academic_year_id" class="finance-form-select" required>
            @foreach($years as $year)
                <option value="{{ $year->id }}" {{ (string) old('academic_year_id', $item->academic_year_id ?? $currentYearId) === (string) $year->id ? 'selected' : '' }}>
                    {{ $year->year }}{{ $year->is_active ? ' (current)' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="finance-form-label" for="term">Term <span class="text-danger">*</span></label>
        <select name="term" id="term" class="finance-form-select" required>
            @foreach([1, 2, 3] as $termNumber)
                <option value="{{ $termNumber }}" {{ (string) old('term', $item->term ?? $currentTerm) === (string) $termNumber ? 'selected' : '' }}>
                    Term {{ $termNumber }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="finance-form-label" for="event_date">Date</label>
        <input type="date" name="event_date" id="event_date" class="finance-form-control"
            value="{{ old('event_date', isset($item->event_date) ? $item->event_date->format('Y-m-d') : '') }}">
    </div>

    <div class="col-md-12" id="voteheadWrap">
        <label class="finance-form-label" for="votehead_id">Votehead</label>
        <select name="votehead_id" id="votehead_id" class="finance-form-select">
            <option value="">Create a votehead from this name</option>
            @foreach($voteheads as $votehead)
                <option value="{{ $votehead->id }}" {{ (string) old('votehead_id', $item->votehead_id ?? '') === (string) $votehead->id ? 'selected' : '' }}>
                    {{ $votehead->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Leave this blank for a trip or fun day. A separate fee line is created so the money is not mixed into school fees.</small>
    </div>

    <div class="col-md-12">
        <label class="finance-form-label" for="description">Notes</label>
        <textarea name="description" id="description" class="finance-form-control" rows="2">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    <div class="col-md-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Open for payment splits</label>
        </div>
    </div>

    <div class="col-md-12" id="chargeWrap">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="charge_class" id="charge_class" value="1" {{ old('charge_class') ? 'checked' : '' }}>
            <label class="form-check-label" for="charge_class">Charge every student in the selected classes now</label>
        </div>
        <small class="text-muted">Adds the amount to each student's invoice for this term. You can also do this later from the activity page. Splitting a payment charges that child even if you skip this.</small>
    </div>
</div>

<div class="mt-4 d-flex gap-3 flex-wrap">
    <button type="submit" class="btn btn-finance btn-finance-success">
        <i class="bi bi-check-circle"></i> Save
    </button>
    <a href="{{ route('finance.extra-income.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kind = document.getElementById('kind');
        const voteheadWrap = document.getElementById('voteheadWrap');
        const chargeWrap = document.getElementById('chargeWrap');
        const classMark = document.getElementById('classRequiredMark');
        const classHelp = document.getElementById('classHelp');
        const classrooms = document.getElementById('classroom_ids');

        function syncKind() {
            const swimming = kind.value === 'swimming';
            voteheadWrap.style.display = swimming ? 'none' : '';
            chargeWrap.style.display = swimming ? 'none' : '';
            classMark.style.display = swimming ? 'none' : '';
            classrooms.required = !swimming;
            classHelp.textContent = swimming
                ? 'Optional for swimming. Leave empty to accept swimming money for every student, or select one or more classes.'
                : 'Hold Ctrl (Windows) or Cmd (Mac) to select more than one class. Only students in the selected classes can be charged or split onto this activity.';
        }

        kind.addEventListener('change', syncKind);
        syncKind();
    });
</script>
