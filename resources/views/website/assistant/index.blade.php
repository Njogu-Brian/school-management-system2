@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Assistant Knowledge', 'icon' => 'bi bi-chat-dots', 'subtitle' => 'Train the public school assistant'])
<div class="settings-card mb-4"><div class="card-body">
    <form action="{{ route('website.assistant.store') }}" method="POST" class="row g-2">@csrf
        <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-2"><input name="topic" class="form-control" placeholder="Topic" required></div>
        <div class="col-md-2"><input name="page_context" class="form-control" placeholder="/admissions, /contact"></div>
        <div class="col-md-1"><input name="priority" type="number" class="form-control" value="0"></div>
        <div class="col-12"><textarea name="content" class="form-control" rows="3" placeholder="Answer content" required></textarea></div>
        <div class="col-12"><button class="btn btn-settings-primary">Add Article</button></div>
    </form>
</div></div>
<div class="settings-card"><div class="card-body p-0">
    @forelse($articles as $article)
    <div class="d-flex justify-content-between align-items-start border-bottom p-3">
        <div><strong>{{ $article->title }}</strong> <span class="badge bg-light text-dark">{{ $article->topic }}</span>
        <p class="text-muted small mb-0">{{ Str::limit($article->content, 120) }}</p></div>
        <form action="{{ route('website.assistant.destroy', $article) }}" method="POST" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
    </div>
    @empty<p class="p-3 text-muted mb-0">No knowledge articles yet.</p>@endforelse
</div></div>
</div></div>
@endsection
