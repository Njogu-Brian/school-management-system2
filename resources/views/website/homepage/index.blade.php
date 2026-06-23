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
                            @foreach(['hero','school_pathway','school_pathways_intro','journey','programs','testimonials','events','cta'] as $type)
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
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order</th>
                                <th>Type</th>
                                <th>Key</th>
                                <th>Title</th>
                                <th>Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sections as $section)
                                <tr>
                                    <td>{{ $section->sort_order }}</td>
                                    <td><span class="pill-badge">{{ $section->section_type }}</span></td>
                                    <td><code>{{ $section->section_key }}</code></td>
                                    <td>{{ $section->title }}</td>
                                    <td>{{ $section->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('website.homepage.sections.destroy', $section) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove section?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No sections yet. Add hero, age journey, and CTA blocks above.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
