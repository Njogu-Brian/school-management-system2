@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page ds-pilot communication-compose-page">
    <div class="settings-shell">
        <x-page-header eyebrow="Communication" title="Compose message" description="Choose a channel, select recipients, preview the message, and send or schedule it." class="mb-3">
            <x-slot:actions>
                <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong"><i class="bi bi-clock-history" aria-hidden="true"></i> Logs</a>
            </x-slot:actions>
        </x-page-header>

        <x-feedback.flash />

        <nav class="compose-channel-tabs" aria-label="Message channel">
            @foreach($channels as $availableChannel)
                <a href="{{ route('communication.compose', ['channel' => $availableChannel]) }}"
                   class="compose-channel-tab {{ $channel === $availableChannel ? 'active' : '' }}"
                   aria-current="{{ $channel === $availableChannel ? 'page' : 'false' }}">
                    <i class="bi bi-{{ $availableChannel === 'sms' ? 'chat-dots' : ($availableChannel === 'email' ? 'envelope' : 'whatsapp') }}" aria-hidden="true"></i>
                    {{ strtoupper($availableChannel) }}
                </a>
            @endforeach
        </nav>

        <x-card class="communication-compose-card">
            @if($channel === 'sms')
                @include('communication.partials.sms-form')
            @elseif($channel === 'email')
                @include('communication.partials.email-form')
            @else
                @include('communication.partials.whatsapp-form')
            @endif
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('submit', function (event) {
    const form = event.target.closest('.communication-compose-card form');
    if (!form) return;
    const button = form.querySelector('button[type="submit"]');
    if (!button || !form.checkValidity()) return;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
});
</script>
@endpush