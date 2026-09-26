@php
    $commNameStyle = old('name_style', setting('communication_name_style', 'full'));
@endphp
<div class="col-12">
    <label class="form-label fw-semibold">Student / staff name in message</label>
    <div class="d-flex flex-wrap gap-3">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="name_style" id="{{ $nameStyleIdPrefix ?? 'name_style' }}_full" value="full" {{ $commNameStyle !== 'first' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $nameStyleIdPrefix ?? 'name_style' }}_full">Full name (First Middle Last)</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="name_style" id="{{ $nameStyleIdPrefix ?? 'name_style' }}_first" value="first" {{ $commNameStyle === 'first' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $nameStyleIdPrefix ?? 'name_style' }}_first">First name only</label>
        </div>
    </div>
    <small class="text-muted">Applies to <code>@{{student_name}}</code> / <code>@{{staff_name}}</code>. Use <code>@{{student_full_name}}</code> or <code>@{{student_first_name}}</code> to force either style in the template.</small>
</div>
