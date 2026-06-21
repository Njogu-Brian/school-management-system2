@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => isset($blog) ? 'Edit Post' : 'New Post', 'icon' => 'bi bi-pencil'])
<div class="settings-card"><div class="card-body">
<form action="{{ isset($blog) ? route('website.blogs.update', $blog) : route('website.blogs.store') }}" method="POST" enctype="multipart/form-data">
@csrf @if(isset($blog)) @method('PUT') @endif
@include('website.blogs._form', ['blog' => $blog ?? null])
<button type="submit" class="btn btn-settings-primary mt-3">Save</button>
<a href="{{ route('website.blogs.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
</form></div></div></div></div>
@endsection
