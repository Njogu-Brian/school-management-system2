@extends('layouts.app')
@section('content')
<div class="container">
    <h4>📜 Email & SMS Logs</h4>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Title</th>
                <th>Target</th>
                <th>Channel</th>
                <th>Status</th>
                <th>Sent At</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->title ?? '-' }}</td>
                <td>{{ ucfirst($log->recipient_type ?? '-') }}</td>
                <td>{{ strtoupper($log->channel) }}</td>
                <td>
                    @if($log->status === 'sent')
                        <span class="badge bg-success">Sent</span>
                    @elseif($log->status === 'failed')
                        <span class="badge bg-danger">Failed</span>
                    @else
                        <span class="badge bg-warning text-dark">{{ ucfirst($log->status) }}</span>
                    @endif
                </td>
                <td>{{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i') : '-' }}</td>
                <td>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#msg{{ $log->id }}">View</button>
                </td>
            </tr>

            <!-- Modal -->
            <div class="modal fade" id="msg{{ $log->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Message Preview</h5></div>
                        <div class="modal-body"><pre>{{ $log->message }}</pre></div>
                        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
                    </div>
                </div>
            </div>
            @endforeach
        </tbody>
    </table>

    {{ $logs->links() }}
</div>
@endsection
