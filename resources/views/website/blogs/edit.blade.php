@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Edit Post', 'icon' => 'bi bi-pencil', 'subtitle' => $blog->title])
<div class="settings-card"><div class="card-body">
<form action="{{ route('website.blogs.update', $blog) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
@include('website.blogs._form', ['blog' => $blog])
<button type="submit" class="btn btn-settings-primary mt-3">Update</button></form></div></div></div></div>
@endsection
