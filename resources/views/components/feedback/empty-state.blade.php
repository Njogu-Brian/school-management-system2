@props(['title' => 'Nothing here yet', 'message' => null, 'icon' => 'bi bi-inbox'])
<div {{ $attributes->merge(['class' => 'ds-empty-state text-center py-5']) }} role="status">
    <i class="{{ $icon }} fs-2 text-muted" aria-hidden="true"></i>
    <h2 class="h6 mt-3 mb-1">{{ $title }}</h2>
    @if($message)<p class="text-muted mb-3">{{ $message }}</p>@endif
    @isset($action){{ $action }}@endisset
</div>
