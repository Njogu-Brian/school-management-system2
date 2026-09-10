@props(['items'])
@if($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator || $items instanceof \Illuminate\Contracts\Pagination\Paginator)
    <nav aria-label="Pagination" class="mt-3">{{ $items->withQueryString()->links() }}</nav>
@endif
