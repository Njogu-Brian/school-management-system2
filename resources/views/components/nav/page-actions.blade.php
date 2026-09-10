@props(['label' => 'Page actions'])
<div {{ $attributes->merge(['class' => 'd-flex flex-wrap gap-2']) }} role="group" aria-label="{{ $label }}">{{ $slot }}</div>
