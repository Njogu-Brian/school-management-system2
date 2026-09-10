@props(['name', 'label', 'value', 'checked' => false])
@php $id = $attributes->get('id', $name . '-' . $value); @endphp
<div class="form-check">
    <input id="{{ $id }}" name="{{ $name }}" type="radio" value="{{ $value }}"
        {{ $attributes->except(['id'])->merge(['class' => 'form-check-input']) }} @checked(old($name, $checked) == $value)>
    <label for="{{ $id }}" class="form-check-label">{{ $label }}</label>
</div>
