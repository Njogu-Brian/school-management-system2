@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', [
    'title' => 'Visual Page Builder',
    'icon' => 'bi bi-columns-gap',
    'subtitle' => $page->name,
])
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('website.pages.preview', $page) }}" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-eye"></i> Preview</a>
    <form action="{{ route('website.builder.snapshot', $page) }}" method="POST" class="d-inline">@csrf
        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-clock-history"></i> Save Version</button>
    </form>
    <a href="{{ route('website.pages.edit', $page) }}" class="btn btn-outline-secondary btn-sm">Page Settings</a>
</div>

<div class="settings-card mb-4">
    <div class="card-header"><strong>Add Block</strong></div>
    <div class="card-body">
        <form action="{{ route('website.builder.add-section', $page) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-8">
                <select name="template_type" class="form-select" required>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->type }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4"><button class="btn btn-settings-primary w-100">Add Section</button></div>
        </form>
    </div>
</div>

<div class="settings-card mb-4">
    <div class="card-header d-flex justify-content-between"><strong>Sections</strong><small class="text-muted">Drag to reorder</small></div>
    <div class="card-body p-0">
        <form id="reorder-form" action="{{ route('website.builder.reorder', $page) }}" method="POST">@csrf
            <ul id="section-list" class="list-group list-group-flush">
                @foreach($sections as $section)
                <li class="list-group-item" draggable="true" data-id="{{ $section->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <span class="pill-badge">{{ $section->section_type }}</span>
                            <strong class="ms-2">{{ $section->title ?: $section->section_key }}</strong>
                            @unless($section->is_active)<span class="badge bg-secondary ms-1">Disabled</span>@endunless
                            <div class="text-muted small mt-1">{{ Str::limit($section->subtitle, 80) }}</div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <form action="{{ route('website.builder.toggle-section', $section) }}" method="POST">@csrf
                                <button class="btn btn-sm btn-outline-secondary" title="Toggle">{{ $section->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form action="{{ route('website.builder.clone-section', $section) }}" method="POST">@csrf
                                <button class="btn btn-sm btn-outline-primary">Clone</button>
                            </form>
                            <form action="{{ route('website.builder.destroy-section', $section) }}" method="POST" onsubmit="return confirm('Remove section?');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </div>
                    </div>
                    @include('website.partials.section-edit-form', [
                        'section' => $section,
                        'updateRoute' => route('website.builder.update-section', $section),
                    ])
                    <input type="hidden" name="order[]" value="{{ $section->id }}">
                </li>
                @endforeach
            </ul>
        </form>
        @if($sections->isNotEmpty())
        <div class="p-3 border-top"><button type="button" id="save-order" class="btn btn-settings-primary btn-sm">Save Order</button></div>
        @endif
    </div>
</div>

@if($snapshots->isNotEmpty())
<div class="settings-card">
    <div class="card-header"><strong>Version History</strong></div>
    <div class="card-body">
        @foreach($snapshots as $snap)
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <span>{{ $snap->label }} <small class="text-muted">{{ $snap->created_at->diffForHumans() }}</small></span>
            <form action="{{ route('website.builder.restore-snapshot', $snap) }}" method="POST" onsubmit="return confirm('Restore this version?');">@csrf
                <button class="btn btn-sm btn-outline-warning">Restore</button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif
</div></div>
@endsection
@push('scripts')
<script>
(function () {
    const list = document.getElementById('section-list');
    if (!list) return;
    let dragEl = null;
    list.querySelectorAll('li').forEach(li => {
        li.addEventListener('dragstart', () => { dragEl = li; li.classList.add('opacity-50'); });
        li.addEventListener('dragend', () => { dragEl = null; li.classList.remove('opacity-50'); });
        li.addEventListener('dragover', e => { e.preventDefault(); if (dragEl && dragEl !== li) list.insertBefore(dragEl, li); });
    });
    document.getElementById('save-order')?.addEventListener('click', () => {
        const form = document.getElementById('reorder-form');
        form.querySelectorAll('input[name="order[]"]').forEach(n => n.remove());
        list.querySelectorAll('li').forEach(li => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'order[]'; input.value = li.dataset.id;
            form.appendChild(input);
        });
        form.submit();
    });
})();
</script>
@endpush
