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
            'subtitle' => 'Images and videos for the public website',
        ])

        <div class="settings-card mb-4">
            <div class="card-body">
                <form action="{{ route('website.media.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-4"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="campus, events"></div>
                    <div class="col-md-3"><label class="form-label">Alt Text</label><input type="text" name="alt_text" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">File</label><input type="file" name="file" class="form-control" required></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featured"><label class="form-check-label" for="featured">Featured</label></div></div>
                    <div class="col-12"><button type="submit" class="btn btn-settings-primary">Upload</button></div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            @forelse($items as $item)
                <div class="col-md-3">
                    <div class="settings-card h-100">
                        <div class="card-body">
                            @if($item->type === 'image')
                                <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}" class="img-fluid rounded mb-2">
                            @else
                                <div class="bg-light rounded p-4 text-center mb-2"><i class="bi bi-file-earmark-play fs-1"></i></div>
                            @endif
                            <div class="fw-semibold">{{ $item->title }}</div>
                            <small class="text-muted d-block mb-2">{{ $item->category ?? 'general' }} · {{ $item->type }}</small>
                            <form action="{{ route('website.media.quality', $item) }}" method="POST" class="mb-2 small">
                                @csrf @method('PATCH')
                                @php $qf = $item->qualityFlag; @endphp
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="approved" value="1" {{ $qf?->approved ? 'checked' : '' }} id="ap{{ $item->id }}"><label class="form-check-label" for="ap{{ $item->id }}">Approved</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="hero_ready" value="1" {{ $qf?->hero_ready ? 'checked' : '' }} id="hr{{ $item->id }}"><label class="form-check-label" for="hr{{ $item->id }}">Hero</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="homepage_ready" value="1" {{ $qf?->homepage_ready ? 'checked' : '' }} id="hp{{ $item->id }}"><label class="form-check-label" for="hp{{ $item->id }}">Homepage</label></div>
                                <input type="number" name="priority" class="form-control form-control-sm d-inline-block w-auto" value="{{ $qf?->priority ?? 0 }}" min="0" max="100" placeholder="Priority">
                                <button class="btn btn-sm btn-outline-primary">Save flags</button>
                            </form>
                            <form action="{{ route('website.media.destroy', $item) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete media?');">
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
