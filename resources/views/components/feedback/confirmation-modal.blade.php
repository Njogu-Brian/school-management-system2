@props(['id', 'title' => 'Confirm action', 'message' => null, 'confirmLabel' => 'Confirm', 'variant' => 'danger', 'form' => null, 'formaction' => null])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title h5" id="{{ $id }}-title">{{ $title }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">{{ $message ?? $slot }}</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                @if($form)<button type="submit" form="{{ $form }}" @if($formaction) formaction="{{ $formaction }}" @endif class="btn btn-{{ $variant }}">{{ $confirmLabel }}</button>@else<button type="button" class="btn btn-{{ $variant }}" data-bs-dismiss="modal">{{ $confirmLabel }}</button>@endif
            </div>
        </div>
    </div>
</div>
