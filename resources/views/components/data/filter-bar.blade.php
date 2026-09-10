@props(['action' => null, 'method' => 'GET', 'title' => 'Filters'])
<form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" @if($action) action="{{ $action }}" @endif {{ $attributes->merge(['class' => 'ds-filter-bar']) }}>
    @if(strtoupper($method) !== 'GET') @csrf @method($method) @endif
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <h2 class="h6 mb-0">{{ $title }}</h2>
        @isset($summary){{ $summary }}@endisset
    </div>
    {{ $slot }}
</form>
