@props(['items' => [], 'active' => null, 'id' => 'tabs'])
<ul {{ $attributes->merge(['class' => 'nav nav-tabs']) }} id="{{ $id }}" role="tablist">
    @foreach($items as $key => $item)
        @php $itemId = $id . '-' . $key; $isActive = (string) $active === (string) $key; @endphp
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $isActive ? 'active' : '' }}" id="{{ $itemId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $itemId }}" type="button" role="tab" aria-controls="{{ $itemId }}" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                {{ is_array($item) ? ($item['label'] ?? $key) : $item }}
            </button>
        </li>
    @endforeach
</ul>
