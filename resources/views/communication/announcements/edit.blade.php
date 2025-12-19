@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Communication / Announcements</div>
                <h1>Edit Announcement</h1>
                <p>Update announcement details.</p>
            </div>
            <a href="{{ route('announcements.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('announcements.update', $announcement) }}" method="POST">
                    @method('PUT')
                    @include('communication.announcements.partials._form', ['announcement' => $announcement])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
