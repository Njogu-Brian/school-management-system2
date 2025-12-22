{{-- Finance Header (uses the same card styling as the rest of the app) --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1">
                    <i class="{{ $icon ?? 'bi bi-currency-dollar' }} me-2"></i>
                    {{ $title ?? 'Finance' }}
                </h4>
                @if(isset($subtitle))
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="d-flex flex-wrap gap-2">
                    {!! $actions !!}
                </div>
            @endif
        </div>
    </div>
</div>

