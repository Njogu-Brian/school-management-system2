@props(['title' => null, 'description' => null])
<section {{ $attributes->merge(['class' => 'ds-section']) }}>
    @if($title || $description)
        <div class="mb-3">
            @if($title)<h2 class="h6 mb-1">{{ $title }}</h2>@endif
            @if($description)<p class="text-muted small mb-0">{{ $description }}</p>@endif
        </div>
    @endif
    {{ $slot }}
</section>
