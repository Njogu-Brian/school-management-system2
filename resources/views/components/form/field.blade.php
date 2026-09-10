@props(['label' => null, 'name' => null, 'required' => false, 'help' => null])
<div {{ $attributes->merge(['class' => 'ds-field']) }}>
    @if($label)
        <label for="{{ $attributes->get('id', $name) }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($help)<div class="form-text">{{ $help }}</div>@endif
    @if($name)<x-form.error :name="$name" />@endif
</div>
