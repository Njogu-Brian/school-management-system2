@props(['variant' => 'secondary'])
@php
    $class = match ($variant) {
        'success' => 'bg-success-subtle text-success-emphasis',
        'warning' => 'bg-warning-subtle text-warning-emphasis',
        'danger' => 'bg-danger-subtle text-danger-emphasis',
        'info' => 'bg-info-subtle text-info-emphasis',
        'primary' => 'bg-primary-subtle text-primary-emphasis',
        default => 'bg-secondary-subtle text-secondary-emphasis',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>{{ $slot }}</span>
