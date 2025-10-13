@extends('layouts.app')
@section('content')
<div class="container">
    <h4 class="mb-3">📋 Email & SMS Log</h4>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
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
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->title ?? '-' }}</td>
                        <td>{{ ucfirst($log->recipient_type ?? $log->target ?? '-') }}</td>
                        <td>{{ strtoupper($log->channel ?? $log->type) }}</td>
                        <td>
                            @if ($log->status === 'sent')
                                <span class="badge bg-success">Sent</span>
                            @elseif ($log->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td>{{ $log->sent_at ? \Carbon\Carbon::parse($log->sent_at)->format('Y-m-d H:i:s') : '-' }}</td>
                        <td>
                            @if($log->message)
                                <button class="btn btn-sm btn-outline-primary view-message" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#messageModal" 
                                        data-message="{{ e($log->message) }}">
                                    View
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No communication logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="messageModalLabel">Message Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="messageContent" style="white-space: pre-wrap;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-message').forEach(btn => {
        btn.addEventListener('click', function() {
            const message = this.getAttribute('data-message');
            document.getElementById('messageContent').innerText = message;
        });
    });
});
</script>
@endsection
