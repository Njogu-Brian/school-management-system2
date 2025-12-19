@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Send Email',
            'icon' => 'bi bi-envelope',
            'subtitle' => 'Compose and send an email to selected recipients',
            'actions' => '<a href="' . route('communication.logs') . '" class="btn btn-ghost-strong"><i class="bi bi-clock-history"></i> Logs</a>'
        ])

        <div class="settings-card">
            <div class="card-body">
                @include('communication.partials.email-form')
            </div>
        </div>
    </div>
</div>
@endsection