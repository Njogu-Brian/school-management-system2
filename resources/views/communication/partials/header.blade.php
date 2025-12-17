{{-- Communication Header Partial --}}
<div class="comm-header comm-animate">
    <div class="comm-header-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h3 class="comm-header-title mb-2">
                    <i class="{{ $icon ?? 'bi bi-chat-dots' }} me-2"></i>
                    {{ $title ?? 'Communication' }}
                </h3>
                @if(isset($subtitle))
                <p class="comm-header-subtitle mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
            <div class="comm-action-buttons mt-3 mt-md-0">
                {!! $actions !!}
            </div>
            @endif
        </div>
    </div>
</div>

