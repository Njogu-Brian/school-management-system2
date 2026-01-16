@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'WhatsApp Sessions',
            'icon' => 'bi bi-whatsapp',
            'subtitle' => 'Create, connect, restart, and delete Wasender sessions',
            'actions' => '<a href="' . route('communication.send.whatsapp') . '" class="btn btn-ghost-strong"><i class="bi bi-send"></i> Send WhatsApp</a>'
        ])

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(!empty($error))
            <div class="alert alert-warning">Fetch error: {{ is_string($error) ? $error : json_encode($error) }}</div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Create Session</h5>
                        <p class="text-muted mb-0">Set up a Wasender session and auto-register our webhook.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('communication.wasender.sessions.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Parent Line" required>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-semibold">Phone (E.164)</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="+2547..." required>
                    </div>
                    <div class="col-12 col-lg-6 d-flex flex-wrap align-items-center gap-3">
                        <label class="form-check-label fw-semibold me-2 text-muted">Options:</label>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="account_protection" value="1" checked id="opt-account-protection">
                            <label class="form-check-label" for="opt-account-protection">Account protection</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="log_messages" value="1" checked id="opt-log">
                            <label class="form-check-label" for="opt-log">Log messages</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="webhook_enabled" value="1" checked id="opt-webhook">
                            <label class="form-check-label" for="opt-webhook">Enable webhook</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button class="btn btn-settings-primary px-4"><i class="bi bi-plus-circle"></i> Create Session</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <h5 class="mb-3">Existing Sessions</h5>
                @if(empty($sessions))
                    <p class="text-muted mb-0">No sessions found.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Account Protection</th>
                                    <th>Webhook</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $s)
                                    <tr>
                                        <td>{{ $s['name'] ?? '—' }}</td>
                                        <td>{{ $s['phone_number'] ?? '—' }}</td>
                                        <td><span class="badge bg-secondary text-uppercase">{{ $s['status'] ?? 'unknown' }}</span></td>
                                        <td>
                                            @if(!empty($s['account_protection']))
                                                <span class="badge bg-warning" title="Rate limited: 1 message per 5 seconds">Enabled</span>
                                            @else
                                                <span class="badge bg-success">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($s['webhook_enabled']))
                                                <span class="badge bg-success">On</span>
                                            @else
                                                <span class="badge bg-secondary">Off</span>
                                            @endif
                                        </td>
                                        <td>{{ $s['updated_at'] ?? '—' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('communication.wasender.sessions.connect', $s['id']) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary">Connect / QR</button>
                                            </form>
                                            <form method="POST" action="{{ route('communication.wasender.sessions.restart', $s['id']) }}" class="d-inline ms-1">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">Restart</button>
                                            </form>
                                            @if(!empty($s['account_protection']))
                                            <form method="POST" action="{{ route('communication.wasender.sessions.update-settings', $s['id']) }}" class="d-inline ms-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="account_protection" value="0">
                                                <button class="btn btn-sm btn-outline-warning" title="Disable account protection to send faster (1 msg per 5 sec limit will be removed)">Disable Protection</button>
                                            </form>
                                            @endif
                                            <form method="POST" action="{{ route('communication.wasender.sessions.destroy', $s['id']) }}" class="d-inline ms-1" onsubmit="return confirm('Delete this session?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection


