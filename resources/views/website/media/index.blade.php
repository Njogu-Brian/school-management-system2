@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Media Library',
            'icon' => 'bi bi-images',
            'subtitle' => 'Upload, optimize, and flag premium photos for the public website',
        ])

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="settings-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Upload media</strong>
                @if($optimizerReady)
                    <span class="badge bg-success">WebP optimization enabled</span>
                @else
                    <span class="badge bg-warning text-dark">WebP optimization unavailable (enable php-gd)</span>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('website.media.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-4"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="campus, hero, testimonials"></div>
                    <div class="col-md-3"><label class="form-label">Alt Text</label><input type="text" name="alt_text" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">File</label><input type="file" name="file" class="form-control" accept="image/*,video/*" required></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featured"><label class="form-check-label" for="featured">Featured</label></div></div>
                    <div class="col-12"><button type="submit" class="btn btn-settings-primary">Upload &amp; Optimize</button></div>
                </form>
            </div>
        </div>

        <div class="settings-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="approved" value="1" @checked(request()->boolean('approved')) id="fap"><label class="form-check-label" for="fap">Approved</label></div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="hero_ready" value="1" @checked(request()->boolean('hero_ready')) id="fhr"><label class="form-check-label" for="fhr">Hero ready</label></div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="homepage_ready" value="1" @checked(request()->boolean('homepage_ready')) id="fhp"><label class="form-check-label" for="fhp">Homepage ready</label></div>
                    </div>
                    <div class="col-md-3"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            @forelse($items as $item)
                @php $qf = $item->qualityFlag; @endphp
                <div class="col-md-4 col-lg-3">
                    <div class="settings-card h-100">
                        <div class="card-body">
                            @if($item->type === 'image')
                                <img src="{{ $item->optimizedUrl() ?? $item->url() }}" alt="{{ $item->alt_text }}" class="img-fluid rounded mb-2" style="aspect-ratio:4/3;object-fit:cover;width:100%">
                            @else
                                <div class="bg-light rounded p-4 text-center mb-2"><i class="bi bi-file-earmark-play fs-1"></i></div>
                            @endif

                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @if($qf?->approved)<span class="badge bg-success">Approved</span>@endif
                                @if($qf?->hero_ready)<span class="badge bg-primary">Hero</span>@endif
                                @if($qf?->homepage_ready)<span class="badge bg-warning text-dark">Homepage</span>@endif
                                @if($qf?->priority)<span class="badge bg-secondary">P{{ $qf->priority }}</span>@endif
                                <span class="badge bg-light text-dark">{{ $item->optimization_status }}</span>
                            </div>

                            <div class="fw-semibold">{{ $item->title }}</div>
                            <small class="text-muted d-block mb-2">{{ $item->category ?? 'general' }} · {{ $item->width }}×{{ $item->height }}</small>

                            @if(is_array($item->variants) && count($item->variants))
                                <small class="text-muted d-block mb-2">Variants: {{ implode(', ', array_keys($item->variants)) }} WebP</small>
                            @endif

                            <form action="{{ route('website.media.quality', $item) }}" method="POST" class="mb-2 small border rounded p-2 bg-light">
                                @csrf @method('PATCH')
                                <div class="mb-1 fw-semibold">Photo quality</div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="approved" value="1" {{ $qf?->approved ? 'checked' : '' }} id="ap{{ $item->id }}"><label class="form-check-label" for="ap{{ $item->id }}">Approved for website</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="hero_ready" value="1" {{ $qf?->hero_ready ? 'checked' : '' }} id="hr{{ $item->id }}"><label class="form-check-label" for="hr{{ $item->id }}">Hero-worthy</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="homepage_ready" value="1" {{ $qf?->homepage_ready ? 'checked' : '' }} id="hp{{ $item->id }}"><label class="form-check-label" for="hp{{ $item->id }}">Homepage-worthy</label></div>
                                <label class="form-label mt-1 mb-0">Priority (0–100)</label>
                                <input type="number" name="priority" class="form-control form-control-sm mb-2" value="{{ $qf?->priority ?? 0 }}" min="0" max="100">
                                <button class="btn btn-sm btn-settings-primary w-100">Save quality flags</button>
                            </form>

                            @if($item->type === 'image' && $optimizerReady)
                                <form action="{{ route('website.media.optimize', $item) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary w-100">Regenerate WebP sizes</button>
                                </form>
                            @endif

                            <form action="{{ route('website.media.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete media?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger w-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-5">No media uploaded yet.</div>
            @endforelse
        </div>
        @if($items->hasPages())<div class="mt-3">{{ $items->links() }}</div>@endif
    </div>
</div>
@endsection
