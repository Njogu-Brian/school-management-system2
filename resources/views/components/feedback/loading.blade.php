@props(['label' => 'Loading', 'inline' => false])
<div {{ $attributes->merge(['class' => $inline ? 'd-inline-flex align-items-center gap-2' : 'ds-loading text-center py-5']) }} role="status" aria-live="polite">
    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
    <span>{{ $label }}<span class="visually-hidden">...</span></span>
</div>
