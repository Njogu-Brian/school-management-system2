@extends('layouts.app')
@section('content')
<div class="container">
    <h4>📋 Email & SMS Log</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Target</th>
                <th>Type</th>
                <th>Status</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->template->title ?? '-' }}</td>
                    <td>{{ ucfirst($log->target_type) }}</td>
                    <td>{{ strtoupper($log->type) }}</td>
                    <td>
                        @if ($log->status === 'sent')
                            <span class="badge bg-success">Sent</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td>{{ $log->sent_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
