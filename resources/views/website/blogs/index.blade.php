@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Blog Posts', 'icon' => 'bi bi-journal-richtext', 'actions' => '<a href="'.route('website.blogs.create').'" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> New Post</a>'])
<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Title</th><th>Author</th><th>Published</th><th></th></tr></thead>
<tbody>@forelse($blogs as $blog)<tr>
<td class="fw-semibold">{{ $blog->title }}</td><td>{{ $blog->author?->name ?? '—' }}</td><td>{{ $blog->published ? $blog->published_at?->format('M d, Y') : 'Draft' }}</td>
<td class="text-end"><a href="{{ route('website.blogs.edit', $blog) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
<form action="{{ route('website.blogs.destroy', $blog) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete post?');">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button></form></td>
</tr>@empty<tr><td colspan="4" class="text-center py-4 text-muted">No blog posts.</td></tr>@endforelse</tbody></table></div>
@if($blogs->hasPages())<div class="p-3">{{ $blogs->links() }}</div>@endif</div></div></div></div>
@endsection
