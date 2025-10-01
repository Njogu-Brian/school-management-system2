<div class="mb-3">
    <label>Skill Name</label>
    <input type="text" name="skill_name" class="form-control" value="{{ old('skill_name',$skill->skill_name ?? '') }}" required>
</div>
<div class="mb-3">
    <label>Rating</label>
    <select name="rating" class="form-select">
        <option value="">-- Select Rating --</option>
        @foreach(['EE'=>'Exceeding Expectation','ME'=>'Meeting Expectation','AE'=>'Above Expectation','BE'=>'Below Expectation'] as $code=>$label)
            <option value="{{ $code }}" @selected(old('rating',$skill->rating ?? '')==$code)>{{ $label }}</option>
        @endforeach
    </select>
</div>
