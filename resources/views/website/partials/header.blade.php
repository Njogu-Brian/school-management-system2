@props(['title', 'icon' => 'bi-globe2', 'subtitle' => null, 'actions' => null])

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="{{ $icon }} me-2"></i>{{ $title }}</h1>
        @if($subtitle)
            <p class="mb-0 opacity-75">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actions)
        <div class="d-flex flex-wrap gap-2">{!! $actions !!}</div>
    @endif
</div>
