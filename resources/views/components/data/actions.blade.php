@props(['label' => 'Actions'])
<div {{ $attributes->merge(['class' => 'd-flex justify-content-end align-items-center gap-2 flex-wrap']) }} role="group" aria-label="{{ $label }}">
    {{ $slot }}
</div>
