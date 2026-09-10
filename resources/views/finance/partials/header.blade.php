@push('styles')
    @include('finance.partials.styles')
@endpush

<x-page-header
    eyebrow="Finance"
    :title="$title ?? 'Finance'"
    :description="$subtitle ?? null"
    :icon="$icon ?? 'bi bi-currency-dollar'"
    class="finance-hero mb-3">
    @if(isset($actions))
        <x-slot:actions>{!! $actions !!}</x-slot:actions>
    @endif
</x-page-header>

