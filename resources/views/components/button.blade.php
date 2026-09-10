@props([
    'variant' => 'primary',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
])
@php
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'subtle', 'ghost' => 'btn-ghost-strong',
        'danger' => 'btn-danger',
        'link' => 'btn-link',
        default => 'btn-primary',
    };
@endphp
<button type="{{ $type }}"
    {{ $attributes->merge(['class' => "btn {$variantClass}"]) }}
    @disabled($disabled || $loading)
    @if($loading) aria-busy="true" data-loading="true" @endif>
    @if($loading)
        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
        <span class="visually-hidden">Working</span>
    @endif
    {{ $slot }}
</button>
