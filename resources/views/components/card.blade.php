@props(['title' => null, 'subtitle' => null, 'flush' => false])
<div {{ $attributes->merge(['class' => 'ds-card card']) }}>
    @if($title || isset($header))
        <div class="card-header d-flex justify-content-between align-items-start gap-2">
            <div>
                @if($title)<h2 class="h6 mb-1">{{ $title }}</h2>@endif
                @if($subtitle)<p class="text-muted small mb-0">{{ $subtitle }}</p>@endif
            </div>
            @isset($header){{ $header }}@endisset
        </div>
    @endif
    <div class="card-body {{ $flush ? 'p-0' : '' }}">{{ $slot }}</div>
</div>
