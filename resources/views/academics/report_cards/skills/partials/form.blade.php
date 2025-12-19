<div class="row g-3">
  <div class="col-md-8">
    <label class="form-label">Skill Name <span class="text-danger">*</span></label>
    <input type="text" name="skill_name" class="form-control" value="{{ old('skill_name',$skill->skill_name ?? '') }}" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Rating <span class="text-danger">*</span></label>
    <select name="rating" class="form-select" required>
      <option value="">-- Select Rating --</option>
      @foreach(['EE'=>'Exceeding Expectation','ME'=>'Meeting Expectation','AE'=>'Above Expectation','BE'=>'Below Expectation'] as $code=>$label)
        <option value="{{ $code }}" @selected(old('rating',$skill->rating ?? '')==$code)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
</div>
