@props(['caption' => null, 'responsive' => true])
<div class="{{ $responsive ? 'table-responsive' : '' }}">
    <table {{ $attributes->merge(['class' => 'table align-middle']) }}>
        @if($caption)<caption class="visually-hidden">{{ $caption }}</caption>@endif
        {{ $slot }}
    </table>
</div>
