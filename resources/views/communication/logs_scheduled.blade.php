@extends('layouts.app')
@section('content')
<div class="container">
    <h4>📆 Scheduled Messages</h4>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
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
                        <td>{{ $item->title ?? ($item->template->title ?? 'N/A') }}</td>
                        <td>{{ Str::limit(strip_tags($item->message ?? ($item->template->content ?? '-')), 80) }}</td>
                        <td>{{ $item->scheduled_at ? $item->scheduled_at->format('M d, Y H:i') : '-' }}</td>
                        <td>{{ strtoupper($item->type ?? $item->channel) }}</td>
                        <td>{{ ucfirst($item->recipient_type ?? $item->target ?? '-') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No scheduled messages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
