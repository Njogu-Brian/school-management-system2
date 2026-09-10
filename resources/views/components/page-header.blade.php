@props(['eyebrow' => null, 'title', 'description' => null, 'icon' => null])
<header {{ $attributes->merge(['class' => 'page-header d-flex align-items-start justify-content-between flex-wrap gap-3']) }}>
    <div>
        @if($eyebrow)<div class="crumb">{{ $eyebrow }}</div>@endif
        <h1>@if($icon)<i class="{{ $icon }} me-2" aria-hidden="true"></i>@endif{{ $title }}</h1>
        @if($description)<p class="mb-0">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="d-flex flex-wrap gap-2">{{ $actions }}</div>@endisset
</header>
