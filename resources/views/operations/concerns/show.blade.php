@extends('layouts.app')

@section('content')
@php
    $statusTone = [
        'open' => 'warning',
        'in_progress' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary',
    ];
@endphp
<div class="settings-page">
    <div class="settings-shell" style="max-width: 880px;">
        <div class="page-header">
            <div class="crumb">
                <a href="{{ route('operations.concerns.index') }}" class="text-decoration-none">Operations</a>
                · Concerns
            </div>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="mb-1">{{ $concern->student?->full_name }}</h1>
                    <p class="mb-0">
                        {{ ucfirst($concern->category) }}
                        · {{ $concern->student?->admission_number }}
                        @if($concern->student?->classroom?->name)
                            · {{ $concern->student->classroom->name }}
                        @endif
                    </p>
                </div>
                <span class="badge bg-{{ $statusTone[$concern->status] ?? 'secondary' }} fs-6">
                    {{ ucfirst(str_replace('_',' ',$concern->status)) }}
                </span>
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-light text-dark border">Raised by {{ $raiser['label'] }}</span>
                <span class="badge bg-light text-dark border">{{ $concern->created_at?->format('M d, Y g:i A') }}</span>
            </div>
            <h2 class="h6 text-muted text-uppercase mb-2">Original concern</h2>
            <p class="mb-0" style="white-space: pre-wrap;">{{ $concern->description }}</p>
        </div>

        <div class="settings-card mb-3">
            <h2 class="h6 text-muted text-uppercase mb-3">Assigned staff</h2>
            <ul class="mb-0 ps-3">
                @forelse($concern->concernedStaff as $s)
                    <li>{{ $s->full_name ?? trim($s->first_name.' '.$s->last_name) }}</li>
                @empty
                    <li class="text-muted">None assigned</li>
                @endforelse
            </ul>
        </div>

        <div class="settings-card mb-3">
            <h2 class="h6 text-muted text-uppercase mb-3">Conversation</h2>
            @forelse($concern->messages as $message)
                <div class="border rounded-3 p-3 mb-3 {{ $message->is_staff ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between gap-2 mb-1">
                        <strong>
                            {{ $message->is_staff ? 'School' : 'Parent' }}
                            @if($message->user?->name)
                                · {{ $message->user->name }}
                            @endif
                        </strong>
                        <span class="small text-muted text-nowrap">{{ $message->created_at?->format('M d, Y g:i A') }}</span>
                    </div>
                    <div style="white-space: pre-wrap;">{{ $message->body }}</div>
                </div>
            @empty
                <p class="text-muted mb-0">No replies yet. Send the first response below.</p>
            @endforelse
        </div>

        @if($concern->status !== 'closed')
            <div class="settings-card mb-3">
                <h2 class="h6 text-muted text-uppercase mb-3">Reply to parent</h2>
                <form method="POST" action="{{ route('operations.concerns.messages.store', $concern->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="body" rows="4" class="form-control @error('body') is-invalid @enderror" required placeholder="Write a reply the parent will see in the mobile app…">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Update status (optional)</label>
                            <select name="status" class="form-select">
                                <option value="">Keep current ({{ ucfirst(str_replace('_',' ',$concern->status)) }})</option>
                                @foreach(['open','in_progress','resolved','closed'] as $st)
                                    <option value="{{ $st }}" @selected(old('status') === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button class="btn btn-settings-primary">Send reply</button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="alert alert-secondary">This concern is closed. Reopen it below if you need to continue the conversation.</div>
        @endif

        <div class="settings-card">
            <h2 class="h6 text-muted text-uppercase mb-3">Status only</h2>
            <form method="POST" action="{{ route('operations.concerns.update', $concern->id) }}" class="d-flex flex-wrap gap-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="flex-grow-1" style="min-width: 200px;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['open','in_progress','resolved','closed'] as $st)
                            <option value="{{ $st }}" @selected($concern->status === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-outline-primary">Save status</button>
            </form>
        </div>
    </div>
</div>
@endsection
