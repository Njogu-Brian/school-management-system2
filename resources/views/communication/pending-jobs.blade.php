{{-- resources/views/communication/pending-jobs.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Pending Queue Jobs',
            'icon' => 'bi bi-hourglass-split',
            'subtitle' => 'View and manage pending communication jobs waiting for queue worker',
            'actions' => ''
        ])

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Job ID</th>
                                <th>Job Type</th>
                                <th>Summary</th>
                                <th>Queue</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                                <th>Available At</th>
                                <th style="width:1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                                @php
                                    $createdAt = \Carbon\Carbon::createFromTimestamp($job['created_at'])->format('Y-m-d H:i:s');
                                    $availableAt = \Carbon\Carbon::createFromTimestamp($job['available_at'])->format('Y-m-d H:i:s');
                                    $isCommunicationJob = $job['is_communication_job'] ?? false;
                                    $jobType = $job['job_type'] ?? 'other';
                                    $jobSummary = $job['job_summary'] ?? ($job['displayName'] ?? 'Unknown Job');
                                @endphp
                                <tr>
                                    <td><code class="text-muted">#{{ $job['id'] }}</code></td>
                                    <td>
                                        @if($isCommunicationJob)
                                            <span class="pill-badge bg-primary">{{ ucfirst($jobType) }}</span>
                                        @else
                                            <span class="pill-badge bg-secondary">Other</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $jobSummary }}</strong>
                                        @if(!empty($job['displayName']))
                                            <br><small class="text-muted">{{ $job['displayName'] }}</small>
                                        @endif
                                    </td>
                                    <td><span class="input-chip">{{ $job['queue'] ?? 'default' }}</span></td>
                                    <td>
                                        <span class="badge {{ $job['attempts'] > 0 ? 'bg-warning' : 'bg-info' }}">
                                            {{ $job['attempts'] }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap"><small>{{ $createdAt }}</small></td>
                                    <td class="text-nowrap"><small>{{ $availableAt }}</small></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if($isCommunicationJob && $jobType === 'whatsapp')
                                                <form action="{{ route('communication.pending-jobs.send-immediately', $job['id']) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to send this job immediately? It will be removed from the queue and processed synchronously. This may take some time for large batches.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Send Immediately">
                                                        <i class="bi bi-send"></i> Send Now
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('communication.pending-jobs.cancel', $job['id']) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to cancel this job? This action cannot be undone.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Cancel Job">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3 mb-0">No pending jobs found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($jobs->hasPages())
                <div class="p-3">
                    {{ $jobs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
