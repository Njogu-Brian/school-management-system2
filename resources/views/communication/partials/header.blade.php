{{-- Communication Header Partial --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <div class="crumb">Communication</div>
        <h1><i class="{{ $icon ?? 'bi bi-chat-dots' }} me-2"></i> {{ $title ?? 'Communication' }}</h1>
        @if(isset($subtitle))
            <p class="mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="mt-2 mt-md-0">
            {!! $actions !!}
        </div>
    @endif
</div>

