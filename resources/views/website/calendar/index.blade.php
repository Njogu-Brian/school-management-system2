@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Content Calendar', 'icon' => 'bi bi-calendar3', 'subtitle' => 'Plan blogs, newsletters & social posts'])
<div class="settings-card mb-4"><div class="card-body">
    <form action="{{ route('website.calendar.store') }}" method="POST" class="row g-2">@csrf
        <div class="col-md-4"><input name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-2"><select name="type" class="form-select" required>
            @foreach(['blog','event_recap','social','devotional','newsletter','holiday'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
        </select></div>
        <div class="col-md-2"><input type="date" name="publish_date" class="form-control"></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value="idea">Idea</option><option value="draft">Draft</option><option value="scheduled">Scheduled</option></select></div>
        <div class="col-md-2"><button class="btn btn-settings-primary w-100">Add</button></div>
        <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notes"></textarea></div>
    </form>
</div></div>
<div class="settings-card"><div class="card-body p-0">
    @forelse($items as $item)
    <div class="d-flex justify-content-between align-items-center border-bottom p-3">
        <div><strong>{{ $item->title }}</strong> <span class="badge bg-light text-dark">{{ $item->type }}</span>
        <small class="text-muted d-block">{{ $item->publish_date?->format('M j, Y') ?? 'No date' }}</small></div>
        <form action="{{ route('website.calendar.update', $item) }}" method="POST" class="d-flex gap-2">@csrf @method('PUT')
            <select name="status" class="form-select form-select-sm"><option value="idea" @selected($item->status==='idea')>Idea</option><option value="draft" @selected($item->status==='draft')>Draft</option><option value="scheduled" @selected($item->status==='scheduled')>Scheduled</option><option value="published" @selected($item->status==='published')>Published</option></select>
            <button class="btn btn-sm btn-outline-primary">Save</button>
        </form>
    </div>
    @empty<p class="p-3 text-muted mb-0">Calendar is empty.</p>@endforelse
</div></div>
</div></div>
@endsection
