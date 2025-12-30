@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Send WhatsApp',
            'icon' => 'bi bi-whatsapp',
            'subtitle' => 'Send or schedule WhatsApp messages via WasenderAPI',
            'actions' => '<a href="' . route('communication.logs') . '" class="btn btn-ghost-strong"><i class="bi bi-clock-history"></i> Logs</a>'
        ])

        @include('communication.partials.flash')

        <div class="settings-card">
            <div class="card-body">
                @include('communication.partials.whatsapp-form')
            </div>
        </div>
    </div>
</div>
@endsection


