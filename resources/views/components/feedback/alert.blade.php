@props(['type' => 'info', 'dismissible' => false, 'title' => null])
<div {{ $attributes->merge(['class' => "alert alert-{$type}" . ($dismissible ? ' alert-dismissible fade show' : '')]) }} role="alert">
    @if($title)<strong>{{ $title }}</strong>@endif
    {{ $slot }}
    @if($dismissible)<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>@endif
</div>
