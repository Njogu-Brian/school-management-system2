{{-- Finance Header Partial --}}
<div class="finance-header finance-animate">
    <div class="finance-header-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h3 class="finance-header-title mb-2">
                    <i class="{{ $icon ?? 'bi bi-currency-dollar' }} me-2"></i>
                    {{ $title ?? 'Finance' }}
                </h3>
                @if(isset($subtitle))
                <p class="finance-header-subtitle mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
            <div class="finance-action-buttons mt-3 mt-md-0">
                {!! $actions !!}
            </div>
            @endif
        </div>
    </div>
</div>

