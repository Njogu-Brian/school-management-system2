@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'WhatsApp Setup',
            'icon' => 'bi bi-whatsapp',
            'subtitle' => 'Meta WhatsApp Cloud API connection status',
            'actions' => '<a href="' . route('communication.send.whatsapp') . '" class="btn btn-ghost-strong"><i class="bi bi-send"></i> Send WhatsApp</a>'
        ])

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(!empty($error))
            <div class="alert alert-warning"><strong>Connection issue:</strong> {{ is_string($error) ? $error : json_encode($error) }}</div>
        @elseif(!empty($connection) && ($connection['status'] ?? '') === 'success')
            <div class="alert alert-success">Connected to Meta WhatsApp Cloud API.</div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Configuration</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Phone Number ID</div>
                        <div class="fw-semibold">{{ $config['phone_number_id'] ?: '— not set —' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Business Account ID</div>
                        <div class="fw-semibold">{{ $config['business_account_id'] ?: '— not set —' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">API Version</div>
                        <div class="fw-semibold">{{ $config['api_version'] ?? 'v21.0' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Default outbound template</div>
                        <div class="fw-semibold">{{ $config['default_template'] ?: '— none (free text, 24h window only) —' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Webhook URL (register in Meta → WhatsApp → Configuration)</div>
                        <div class="fw-semibold font-monospace">{{ $config['webhook_url'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($connection) && ($connection['status'] ?? '') === 'success' && is_array($connection['body'] ?? null))
            <div class="settings-card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Registered number</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Display number</div>
                            <div class="fw-semibold">{{ data_get($connection, 'body.display_phone_number', '—') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Verified name</div>
                            <div class="fw-semibold">{{ data_get($connection, 'body.verified_name', '—') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Quality rating</div>
                            <div class="fw-semibold">{{ data_get($connection, 'body.quality_rating', '—') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-body">
                <h5 class="mb-2">Setup notes</h5>
                <ul class="mb-0 text-muted">
                    <li>Bulk fee reminders and announcements require an <strong>approved Meta message template</strong> with a <code>{{1}}</code> body variable. Set <code>WHATSAPP_DEFAULT_TEMPLATE</code> in <code>.env</code> after approval.</li>
                    <li>Free-text messages only work when a parent has messaged you within the last 24 hours.</li>
                    <li>Wasender QR sessions are no longer used — manage your number in <a href="https://business.facebook.com" target="_blank" rel="noopener">Meta Business Manager</a>.</li>
                    <li>Use the same verify token in Meta webhook settings as <code>WHATSAPP_WEBHOOK_VERIFY_TOKEN</code> in <code>.env</code>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
