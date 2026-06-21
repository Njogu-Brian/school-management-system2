@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Edit Page',
            'icon' => 'bi bi-pencil-square',
            'subtitle' => $page->name,
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('website.pages.update', $page) }}" method="POST">
                    @csrf @method('PUT')
                    @include('website.pages._form', ['page' => $page])
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary">Update Page</button>
                        <a href="{{ route('website.pages.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
