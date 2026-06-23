@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Homepage Builder',
            'icon' => 'bi bi-layout-wtf',
            'subtitle' => 'Compose homepage sections consumed by the Next.js frontend',
            'actions' => '<a href="' . route('website.builder.show', $page) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-columns-gap"></i> Visual Builder</a>',
        ])

        <div class="settings-card mb-4">
            <div class="card-header"><strong>Add Section</strong></div>
            <div class="card-body">
                <form action="{{ route('website.homepage.sections.store') }}" method="POST" class="row g-3">
                    @csrf
                    <input type="hidden" name="page_id" value="{{ $page->id }}">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="section_type" class="form-select" required>
                            @foreach(['hero','school_story','school_pathway','school_pathways_intro','journey','programs','testimonials','events','cta','page_hero','rich_text','cta_banner'] as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Key</label>
                        <input type="text" name="section_key" class="form-control" placeholder="hero_main" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $sections->count() }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Content (body copy for school_pathway cards)</label>
                        <textarea name="content" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Settings (JSON)</label>
                        <textarea name="settings" class="form-control font-monospace" rows="3" placeholder='{"cta_label":"Explore Early Years","link_url":"/academics#early-years","image_url":"https://..."}'></textarea>
                        <small class="text-muted">For <code>school_pathway</code>: cta_label, link_url, image_url. Use <code>school_pathways_intro</code> for section subtitle only.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-plus"></i> Add Section</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header"><strong>Sections</strong> <small class="text-muted">— expand to edit text, images (settings JSON), and buttons</small></div>
            <div class="card-body p-0">
                @forelse($sections as $section)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="pill-badge">{{ $section->section_type }}</span>
                                <strong class="ms-2">{{ $section->title ?: $section->section_key }}</strong>
                                <span class="text-muted small ms-2">order {{ $section->sort_order }}</span>
                                @unless($section->is_active)<span class="badge bg-secondary">Off</span>@endunless
                            </div>
                            <form action="{{ route('website.homepage.sections.destroy', $section) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove section?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        @include('website.partials.section-edit-form', [
                            'section' => $section,
                            'updateRoute' => route('website.builder.update-section', $section),
                        ])
                    </div>
                @empty
                    <p class="text-center text-muted py-4">No sections yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
