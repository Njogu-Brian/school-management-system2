@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Scheduled Messages',
            'icon' => 'bi bi-calendar-clock',
            'subtitle' => 'View and manage scheduled SMS and email messages',
            'actions' => ''
        ])

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Scheduled At</th>
                                <th>Channel</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $item)
                                <tr>
                                    <td><strong>{{ $item->title ?? ($item->template->title ?? 'N/A') }}</strong></td>
                                    <td><small class="text-muted">{{ Str::limit(strip_tags($item->message ?? ($item->template->content ?? '-')), 80) }}</small></td>
                                    <td>
                                        <span class="pill-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $item->scheduled_at ? $item->scheduled_at->format('M d, Y H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td><span class="pill-badge">{{ strtoupper($item->type ?? $item->channel) }}</span></td>
                                    <td><span class="input-chip">{{ ucfirst($item->recipient_type ?? $item->target ?? '-') }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3 mb-0">No scheduled messages found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                <div class="p-3">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
