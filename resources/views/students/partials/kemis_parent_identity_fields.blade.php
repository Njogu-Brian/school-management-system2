@php
  $slot = $slot ?? 'father';
  $parent = $parent ?? null;
  $parts = $parent ? $parent->formNameParts($slot) : ['first' => '', 'middle' => '', 'last' => ''];
  $title = $title ?? ucfirst($slot);
  $requiredNote = $requiredNote ?? '';
  $showRelationship = $showRelationship ?? false;
@endphp
<div class="col-12"><div class="fw-semibold mb-1">{{ $title }} {!! $requiredNote !!}</div></div>
<div class="col-md-4">
  <label class="form-label">First Name</label>
  <input type="text" name="{{ $slot }}_first_name" value="{{ old($slot.'_first_name', $parts['first']) }}" class="form-control">
</div>
<div class="col-md-4">
  <label class="form-label">Middle Name</label>
  <input type="text" name="{{ $slot }}_middle_name" value="{{ old($slot.'_middle_name', $parts['middle']) }}" class="form-control">
</div>
<div class="col-md-4">
  <label class="form-label">Last Name</label>
  <input type="text" name="{{ $slot }}_last_name" value="{{ old($slot.'_last_name', $parts['last']) }}" class="form-control">
</div>
@if($showRelationship)
<div class="col-md-4">
  <label class="form-label">Relationship</label>
  <input type="text" name="guardian_relationship" value="{{ old('guardian_relationship', $parent->guardian_relationship ?? '') }}" class="form-control" placeholder="e.g. Aunt, Uncle, Grandparent">
</div>
@endif
<div class="col-md-4">
  <label class="form-label">Type of ID</label>
  <select name="{{ $slot }}_id_type" class="form-select">
    <option value="">Select</option>
    @foreach(config('kemis.id_types') as $idType)
      <option value="{{ $idType }}" @selected(old($slot.'_id_type', $parent->{$slot.'_id_type'} ?? '') === $idType)>{{ $idType }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-4">
  <label class="form-label">National ID No.</label>
  <input type="text" name="{{ $slot }}_id_number" value="{{ old($slot.'_id_number', $parent->{$slot.'_id_number'} ?? '') }}" class="form-control">
</div>
<div class="col-md-4">
  <label class="form-label">Country of Residence</label>
  <select name="{{ $slot }}_country_of_residence" class="form-select">
    <option value="">Select</option>
    @foreach(config('kemis.countries_of_residence') as $country)
      <option value="{{ $country }}" @selected(old($slot.'_country_of_residence', $parent->{$slot.'_country_of_residence'} ?? '') === $country)>{{ $country }}</option>
    @endforeach
  </select>
</div>
