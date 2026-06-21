@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => isset($page) ? 'Edit Page' : 'New Page',
            'icon' => 'bi bi-file-earmark-plus',
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ isset($page) ? route('website.pages.update', $page) : route('website.pages.store') }}" method="POST">
                    @csrf
                    @if(isset($page)) @method('PUT') @endif

                    @include('website.pages._form', ['page' => $page ?? null])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary">Save Page</button>
                        <a href="{{ route('website.pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
