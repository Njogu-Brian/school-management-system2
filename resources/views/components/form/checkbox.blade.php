@props(['name', 'label', 'value' => '1', 'checked' => false])
@php $id = $attributes->get('id', $name); @endphp
<div class="form-check">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}"
        {{ $attributes->except(['id'])->merge(['class' => 'form-check-input']) }} @checked(old($name, $checked))>
    <label for="{{ $id }}" class="form-check-label">{{ $label }}</label>
</div>
