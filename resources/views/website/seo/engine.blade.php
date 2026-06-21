@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'SEO Engine', 'icon' => 'bi bi-search-heart', 'subtitle' => 'Keywords, local areas & content scoring'])
@if(!$nap['consistent'])<div class="alert alert-warning">NAP incomplete — ensure school name, phone, and address are set in Site Settings.</div>@endif
<div class="settings-card mb-4"><div class="card-header">Keyword Tracking</div><div class="card-body">
    <form action="{{ route('website.seo-engine.keywords.store') }}" method="POST" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><input name="keyword" class="form-control" placeholder="Keyword" required></div>
        <div class="col-md-3"><select name="page_id" class="form-select"><option value="">— Page —</option>@foreach($pages as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="search_volume" type="number" class="form-control" placeholder="Volume"></div>
        <div class="col-md-2"><select name="priority" class="form-select"><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select></div>
        <div class="col-md-2"><button class="btn btn-settings-primary w-100">Add</button></div>
    </form>
    @forelse($keywords as $kw)<div class="d-flex justify-content-between py-2 border-bottom"><span>{{ $kw->keyword }} @if($kw->page)<small class="text-muted">→ {{ $kw->page->name }}</small>@endif</span><span class="badge bg-light text-dark">{{ $kw->priority }}</span></div>@empty<p class="text-muted">No keywords yet.</p>@endforelse
</div></div>
<div class="settings-card mb-4"><div class="card-header">Local Service Areas</div><div class="card-body">
    @foreach($areas as $area)
    <form action="{{ route('website.seo-engine.areas.update', $area) }}" method="POST" class="border rounded p-3 mb-3">@csrf @method('PUT')
        <h6>{{ $area->area_name }}</h6>
        <input name="headline" class="form-control mb-2" value="{{ $area->headline }}" required>
        <textarea name="description" class="form-control mb-2" rows="2">{{ $area->description }}</textarea>
        <textarea name="map_embed" class="form-control mb-2" rows="2" placeholder="Google Maps embed HTML">{{ $area->map_embed }}</textarea>
        <label class="form-check"><input type="checkbox" name="published" value="1" class="form-check-input" @checked($area->published)> Published</label>
        <button class="btn btn-sm btn-settings-primary mt-2">Save</button>
    </form>
    @endforeach
</div></div>
@if(count($duplicates))<div class="alert alert-danger">Duplicate page titles detected: {{ implode(', ', $duplicates) }}</div>@endif
</div></div>
@endsection
