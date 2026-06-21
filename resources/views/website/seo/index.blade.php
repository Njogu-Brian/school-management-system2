@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'SEO Manager', 'icon' => 'bi bi-search', 'subtitle' => 'Default metadata and per-page SEO'])

<div class="settings-card mb-4"><div class="card-header">Default SEO</div><div class="card-body">
<form action="{{ route('website.seo.defaults') }}" method="POST">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Default Title</label><input type="text" name="seo_defaults[title]" class="form-control" value="{{ old('seo_defaults.title', $settings->seo_defaults['title'] ?? '') }}"></div>
<div class="col-md-6"><label class="form-label">Keywords</label><input type="text" name="seo_defaults[keywords]" class="form-control" value="{{ old('seo_defaults.keywords', $settings->seo_defaults['keywords'] ?? '') }}"></div>
<div class="col-12"><label class="form-label">Default Description</label><textarea name="seo_defaults[description]" class="form-control" rows="3">{{ old('seo_defaults.description', $settings->seo_defaults['description'] ?? '') }}</textarea></div>
<div class="col-12"><label class="form-label">OG Image URL</label><input type="text" name="seo_defaults[og_image]" class="form-control" value="{{ old('seo_defaults.og_image', $settings->seo_defaults['og_image'] ?? '') }}"></div>
</div>
<button type="submit" class="btn btn-settings-primary mt-3">Save Defaults</button>
</form></div></div>

<div class="settings-card"><div class="card-header">Per-Page SEO</div><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Page</th><th>Meta Title</th><th>Meta Description</th><th></th></tr></thead>
<tbody>@foreach($pages as $page)<tr>
<td>{{ $page->name }}<br><code>/{{ $page->slug }}</code></td>
<td colspan="2">
<form action="{{ route('website.seo.page', $page) }}" method="POST" class="row g-2">@csrf @method('PUT')
<div class="col-md-4"><input type="text" name="meta_title" class="form-control form-control-sm" value="{{ $page->meta_title }}" placeholder="Meta title"></div>
<div class="col-md-6"><input type="text" name="meta_description" class="form-control form-control-sm" value="{{ $page->meta_description }}" placeholder="Meta description"></div>
<div class="col-md-2"><button class="btn btn-sm btn-settings-primary w-100">Save</button></div>
</form></td></tr>@endforeach</tbody></table></div></div></div></div></div>
@endsection
