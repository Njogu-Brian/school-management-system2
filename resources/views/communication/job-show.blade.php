{{-- resources/views/communication/job-show.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => $communicationJob->title ?: 'Job #' . $communicationJob->id,
            'icon' => 'bi bi-people',
            'subtitle' => strtoupper($communicationJob->channel) . ' · ' . str_replace('_', ' ', $communicationJob->source) . ' · ' . ucfirst($communicationJob->status),
            'actions' => '<a href="' . route('communication.pending-jobs') . '" class="btn btn-ghost-strong btn-sm">Back to Bulk jobs</a>'
        ])

        @include('communication.partials.flash')

        <div class="settings-card mb-3">
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-md-3"><strong>Status</strong><br><span class="badge {{ $communicationJob->statusBadgeClass() }}">{{ ucfirst($communicationJob->status) }}</span></div>
                    <div class="col-md-3"><strong>Recipients</strong><br>{{ $communicationJob->recipient_count }}</div>
                    <div class="col-md-3"><strong>Sent / Failed / Skipped</strong><br>{{ $communicationJob->sent_count }} / {{ $communicationJob->failed_count }} / {{ $communicationJob->skipped_count }}</div>
                    <div class="col-md-3"><strong>Tracking</strong><br><code>{{ $communicationJob->tracking_id }}</code></div>
                </div>
                @if($communicationJob->message)
                    <hr>
                    <div class="small text-muted" style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($communicationJob->message, 500) }}</div>
                @endif
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    @if($communicationJob->isPausable())
                        <form action="{{ route('communication.pending-jobs.pause', $communicationJob) }}" method="POST">@csrf
                            <button class="btn btn-sm btn-outline-warning" type="submit">Pause</button>
                        </form>
                    @endif
                    @if($communicationJob->isResumable())
                        <form action="{{ route('communication.pending-jobs.resume-job', $communicationJob) }}" method="POST">@csrf
                            <button class="btn btn-sm btn-outline-success" type="submit">Resume</button>
                        </form>
                    @endif
                    @if($communicationJob->isCancellable())
                        <form action="{{ route('communication.pending-jobs.cancel', $communicationJob->id) }}" method="POST"
                              onsubmit="return confirm('Cancel remaining recipients?');">@csrf
                            <button class="btn btn-sm btn-outline-danger" type="submit">Stop</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Error</th>
                                <th>Sent at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recipients as $r)
                                <tr>
                                    <td>{{ $r->name ?: '—' }}</td>
                                    <td><code>{{ $r->contact ?: '—' }}</code></td>
                                    <td><span class="badge bg-secondary">{{ $r->status }}</span></td>
                                    <td class="small text-muted">{{ $r->error_code ?: $r->error_message }}</td>
                                    <td class="small">{{ $r->sent_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted p-4">No recipients stored for this job.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($recipients->hasPages())
                <div class="card-footer">{{ $recipients->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
