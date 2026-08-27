@php
  /** @var \App\Models\Student|null $student */
  $student = $student ?? null;
  $htmlPrefix = $htmlPrefix ?? '';
  $oldPrefix = $oldPrefix ?? '';
  $fieldIdSuffix = $fieldIdSuffix ?? '';
  $nameFor = function (string $field) use ($htmlPrefix) {
      return $htmlPrefix !== '' ? "{$htmlPrefix}[{$field}]" : $field;
  };
  $oldFor = function (string $field, $default = null) use ($oldPrefix) {
      $path = $oldPrefix !== '' ? "{$oldPrefix}.{$field}" : $field;
      return old($path, $default);
  };
  $idFor = function (string $field) use ($fieldIdSuffix) {
      return $fieldIdSuffix !== '' ? "{$field}_{$fieldIdSuffix}" : $field;
  };
  $religionState = \App\Support\KemisProfile::religionFormState($oldFor('religion', $student?->religion ?? ''));
  $interestState = \App\Support\KemisProfile::interestsFormState($oldFor('learner_interests', $student?->learner_interests ?? []));
  $interestOther = $oldFor('learner_interests_other', $interestState['other']);
  $religionOther = $oldFor('religion_other', $religionState['other']);
  $religionSelected = old($oldPrefix !== '' ? "{$oldPrefix}.religion" : 'religion', $religionState['selected']);
  $sneValue = (string) $oldFor('has_special_needs', ($student?->has_special_needs ?? false) ? '1' : '0');
@endphp

<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('nationality') }}">Nationality / Country of Birth <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('nationality') }}" id="{{ $idFor('nationality') }}" class="form-select" required>
    <option value="">Select</option>
    @foreach(config('kemis.nationalities') as $nationality)
      <option value="{{ $nationality }}" @selected($oldFor('nationality', $student?->nationality ?? 'Kenyan') === $nationality)>{{ $nationality }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('county_of_birth') }}">County of Birth <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('county_of_birth') }}" id="{{ $idFor('county_of_birth') }}" class="form-select" required>
    <option value="">Select</option>
    @foreach(config('kemis.counties') as $county)
      <option value="{{ $county }}" @selected($oldFor('county_of_birth', $student?->county_of_birth ?? '') === $county)>{{ $county }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('sub_county_of_birth') }}">Sub-County of Birth <span class="text-danger">*</span></label>
  <input type="text" name="{{ $nameFor('sub_county_of_birth') }}" id="{{ $idFor('sub_county_of_birth') }}" class="form-control" value="{{ $oldFor('sub_county_of_birth', $student?->sub_county_of_birth ?? '') }}" required>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('location_of_birth') }}">Location of Birth <span class="text-danger">*</span></label>
  <input type="text" name="{{ $nameFor('location_of_birth') }}" id="{{ $idFor('location_of_birth') }}" class="form-control" value="{{ $oldFor('location_of_birth', $student?->location_of_birth ?? '') }}" required>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('birth_certificate_entry_no') }}">Birth Certificate Entry No. <span class="text-danger">*</span></label>
  <input type="text" name="{{ $nameFor('birth_certificate_entry_no') }}" id="{{ $idFor('birth_certificate_entry_no') }}" class="form-control" value="{{ $oldFor('birth_certificate_entry_no', $student?->birth_certificate_entry_no ?? '') }}" placeholder="As on birth certificate" required>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('medical_condition') }}">Medical Condition <span class="text-danger">*</span></label>
  <input type="text" name="{{ $nameFor('medical_condition') }}" id="{{ $idFor('medical_condition') }}" class="form-control" value="{{ $oldFor('medical_condition', $student?->medical_condition ?? '') }}" placeholder="None if not applicable" required>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('religion') }}">Religion <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('religion') }}" id="{{ $idFor('religion') }}" class="form-select kemis-religion-select" data-other-target="{{ $idFor('religion_other_wrap') }}" required>
    <option value="">Select</option>
    @foreach(config('kemis.religions') as $religion)
      <option value="{{ $religion }}" @selected($religionSelected === $religion)>{{ $religion }}</option>
    @endforeach
  </select>
  <div id="{{ $idFor('religion_other_wrap') }}" class="mt-2" style="{{ $religionSelected === 'Other' ? '' : 'display:none' }}">
    <input type="text" name="{{ $nameFor('religion_other') }}" class="form-control" value="{{ $religionOther }}" placeholder="Specify religion">
  </div>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('orphan_status') }}">Orphan status <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('orphan_status') }}" id="{{ $idFor('orphan_status') }}" class="form-select" required>
    <option value="">Select</option>
    @foreach(config('kemis.orphan_statuses') as $value => $label)
      <option value="{{ $value }}" @selected($oldFor('orphan_status', $student?->orphan_status ?? '') === $value)>{{ $label }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-4">
  <label class="form-label" for="{{ $idFor('has_special_needs') }}">SNE / Disability <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('has_special_needs') }}" id="{{ $idFor('has_special_needs') }}" class="form-select kemis-sne-select" data-disability-target="{{ $idFor('disability_type_wrap') }}" required>
    <option value="0" @selected($sneValue === '0' || $sneValue === '')>No</option>
    <option value="1" @selected($sneValue === '1' || $sneValue === 'true')>Yes</option>
  </select>
</div>
<div class="col-md-4" id="{{ $idFor('disability_type_wrap') }}" style="{{ in_array($sneValue, ['1', 'true'], true) ? '' : 'display:none' }}">
  <label class="form-label" for="{{ $idFor('disability_type') }}">Disability Type <span class="text-danger">*</span></label>
  <select name="{{ $nameFor('disability_type') }}" id="{{ $idFor('disability_type') }}" class="form-select" @if(in_array($sneValue, ['1', 'true'], true)) required @endif>
    <option value="">Select</option>
    @foreach(config('kemis.disability_types') as $type)
      <option value="{{ $type }}" @selected($oldFor('disability_type', $student?->disability_type ?? '') === $type)>{{ $type }}</option>
    @endforeach
  </select>
</div>
<div class="col-12">
  <label class="form-label">Learner Interests <span class="text-danger">*</span></label>
  <div class="d-flex flex-wrap gap-3">
    @foreach(config('kemis.learner_interests') as $interest)
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="{{ $nameFor('learner_interests') }}[]" id="{{ $idFor('interest_'.$interest) }}" value="{{ $interest }}" @checked(in_array($interest, $interestState['selected'], true))>
        <label class="form-check-label" for="{{ $idFor('interest_'.$interest) }}">{{ $interest }}</label>
      </div>
    @endforeach
  </div>
  <input type="text" name="{{ $nameFor('learner_interests_other') }}" class="form-control mt-2" value="{{ $interestOther }}" placeholder="Other interest (counts toward required if none selected)">
</div>
@once
<script>
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('kemis-religion-select')) {
        var wrap = document.getElementById(e.target.dataset.otherTarget);
        if (wrap) wrap.style.display = e.target.value === 'Other' ? '' : 'none';
    }
    if (e.target.classList.contains('kemis-sne-select')) {
        var wrap = document.getElementById(e.target.dataset.disabilityTarget);
        if (wrap) {
            wrap.style.display = e.target.value === '1' ? '' : 'none';
            var sel = wrap.querySelector('select');
            if (sel) sel.required = e.target.value === '1';
        }
    }
});
</script>
@endonce
