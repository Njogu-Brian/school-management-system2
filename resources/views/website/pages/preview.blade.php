@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="h3">Preview: {{ $page->title }}</h1>
    <p class="text-muted">Status: {{ $page->status }}</p>
    @foreach($page->sections as $section)
        <div class="border rounded p-3 mb-3">
            <h2>{{ $section->title }}</h2>
            <p>{{ $section->subtitle }}</p>
            <div>{!! $section->content !!}</div>
        </div>
    @endforeach
</div>
@endsection
