@props(['label', 'value', 'trend' => null, 'icon' => null])
<div {{ $attributes->merge(['class' => 'ds-stat-card card h-100']) }}>
    <div class="card-body d-flex align-items-start justify-content-between gap-3">
        <div><div class="text-muted small">{{ $label }}</div><div class="h4 mb-0">{{ $value }}</div>
            @if($trend)<div class="small mt-1">{{ $trend }}</div>@endif
        </div>
        @if($icon)<span class="ds-stat-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>@endif
    </div>
</div>
