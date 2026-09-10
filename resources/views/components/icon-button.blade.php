@props([
    'label',
    'variant' => 'subtle',
    'type' => 'button',
    'disabled' => false,
])
@php
    $variantClass = $variant === 'danger' ? 'btn-outline-danger' : ($variant === 'primary' ? 'btn-primary' : 'btn-ghost-strong');
@endphp
<button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}"
    {{ $attributes->merge(['class' => "btn btn-icon {$variantClass}"]) }}
    @disabled($disabled)>
    {{ $slot }}
</button>
