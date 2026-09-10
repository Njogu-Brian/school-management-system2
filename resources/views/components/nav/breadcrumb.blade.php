@props(['items' => []])
<nav aria-label="Breadcrumb">
    <ol {{ $attributes->merge(['class' => 'breadcrumb']) }}>
        @foreach($items as $label => $url)
            @if($url)
                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
            @endif
        @endforeach
    </ol>
</nav>
