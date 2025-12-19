@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Send SMS',
            'icon' => 'bi bi-chat-dots',
            'subtitle' => 'Compose and send an SMS to selected recipients',
            'actions' => '<a href="' . route('communication.logs') . '" class="btn btn-ghost-strong"><i class="bi bi-clock-history"></i> Logs</a>'
        ])

        <div class="settings-card">
            <div class="card-body">
                @include('communication.partials.sms-form')
            </div>
        </div>
    </div>
</div>
@endsection