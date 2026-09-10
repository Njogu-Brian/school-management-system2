@props(['name'])
@error($name)
    <div id="{{ $name }}-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
@enderror
