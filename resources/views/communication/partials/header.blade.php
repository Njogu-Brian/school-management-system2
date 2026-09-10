<x-page-header
    eyebrow="Communication"
    :title="$title ?? 'Communication'"
    :description="$subtitle ?? null"
    :icon="$icon ?? 'bi bi-chat-dots'"
    class="mb-3">
    @if(isset($actions))
        <x-slot:actions>{!! $actions !!}</x-slot:actions>
    @endif
</x-page-header>

