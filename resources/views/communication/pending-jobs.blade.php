{{-- resources/views/communication/pending-jobs.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Queue Jobs',
            'icon' => 'bi bi-hourglass-split',
            'subtitle' => 'View and manage all communication jobs (pending, completed, and failed)',
            'actions' => ''
        ])

        {{-- Status Filter --}}
        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('communication.pending-jobs') }}" class="d-flex gap-2 align-items-center flex-wrap">
                    <label class="fw-semibold mb-0">Filter by Status:</label>
                    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Jobs</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Job ID</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Recipients</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th style="width:1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                                @php
                                    $status = $job['status'] ?? 'unknown';
                                    $statusClass = match($status) {
                                        'pending' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = ucfirst($status);
                                    $jobId = $job['id'] ?? $job['uuid'] ?? 'N/A';
                                    $messagePreview = \Illuminate\Support\Str::limit($job['message'] ?? 'No message', 50, '...');
                                    $recipientCount = $job['recipient_count'] ?? 0;
                                    $userName = $job['user_name'] ?? 'Unknown';
                                    $createdAt = $job['created_at_formatted'] ?? '-';
                                @endphp
                                <tr>
                                    <td>
                                        <code class="text-muted">#{{ $jobId }}</code>
                                        @if(!empty($job['trackingId']))
                                            <br><small class="text-muted">{{ $job['trackingId'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                        @if($status === 'completed' && isset($job['sent_count']))
                                            <br><small class="text-muted">
                                                Sent: {{ $job['sent_count'] ?? 0 }}, 
                                                Failed: {{ $job['failed_count'] ?? 0 }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($job['is_communication_job'] ?? false)
                                            <span class="pill-badge bg-primary">{{ ucfirst($job['job_type'] ?? 'communication') }}</span>
                                        @else
                                            <span class="pill-badge bg-secondary">Other</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $job['title'] ?? 'No Title' }}</strong>
                                        @if($messagePreview)
                                            <br><small class="text-muted">{{ $messagePreview }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $recipientCount }} recipients</span>
                                    </td>
                                    <td>
                                        <small>{{ $userName }}</small>
                                        @if(!empty($job['user_email']))
                                            <br><small class="text-muted">{{ $job['user_email'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap"><small>{{ $createdAt }}</small></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-ghost-strong"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#jobModal{{ md5($jobId) }}">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            @if($status === 'pending' && ($job['is_communication_job'] ?? false) && ($job['job_type'] ?? '') === 'whatsapp' && !empty($job['id']))
                                                <form action="{{ route('communication.pending-jobs.send-immediately', $job['id']) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to send this job immediately? It will be removed from the queue and processed synchronously.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Send Immediately">
                                                        <i class="bi bi-send"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('communication.pending-jobs.cancel', $job['id']) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to cancel this job? This action cannot be undone.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Cancel Job">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Job Details Modal --}}
                                <div class="modal fade" id="jobModal{{ md5($jobId) }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Job Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-3">Job ID</dt>
                                                    <dd class="col-sm-9"><code>{{ $jobId }}</code></dd>
                                                    
                                                    @if(!empty($job['trackingId']))
                                                        <dt class="col-sm-3">Tracking ID</dt>
                                                        <dd class="col-sm-9"><code>{{ $job['trackingId'] }}</code></dd>
                                                    @endif

                                                    <dt class="col-sm-3">Status</dt>
                                                    <dd class="col-sm-9">
                                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                                        @if($status === 'completed' && isset($job['sent_count']))
                                                            <br><small class="text-muted mt-1 d-inline-block">
                                                                Sent: {{ $job['sent_count'] ?? 0 }}, 
                                                                Failed: {{ $job['failed_count'] ?? 0 }}, 
                                                                Skipped: {{ $job['skipped_count'] ?? 0 }}
                                                            </small>
                                                        @endif
                                                    </dd>

                                                    <dt class="col-sm-3">Job Type</dt>
                                                    <dd class="col-sm-9">
                                                        {{ $job['job_class'] ?? ($job['displayName'] ?? 'Unknown') }}
                                                    </dd>

                                                    <dt class="col-sm-3">Title</dt>
                                                    <dd class="col-sm-9"><strong>{{ $job['title'] ?? 'No Title' }}</strong></dd>

                                                    <dt class="col-sm-3">Message</dt>
                                                    <dd class="col-sm-9">
                                                        <pre class="mb-0" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;">{{ $job['message'] ?? 'No message' }}</pre>
                                                    </dd>

                                                    <dt class="col-sm-3">Recipients</dt>
                                                    <dd class="col-sm-9">
                                                        <strong>{{ $recipientCount }}</strong> recipients
                                                        @if(!empty($job['recipients']) && is_array($job['recipients']))
                                                            <div class="mt-2" style="max-height: 150px; overflow-y: auto;">
                                                                <small>
                                                                    @foreach(array_slice($job['recipients'], 0, 20) as $phone => $recipient)
                                                                        <div>{{ $phone }}</div>
                                                                    @endforeach
                                                                    @if(count($job['recipients']) > 20)
                                                                        <div class="text-muted">... and {{ count($job['recipients']) - 20 }} more</div>
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </dd>

                                                    <dt class="col-sm-3">Target</dt>
                                                    <dd class="col-sm-9">{{ ucfirst($job['target'] ?? '-') }}</dd>

                                                    @if(!empty($job['mediaUrl']))
                                                        <dt class="col-sm-3">Media URL</dt>
                                                        <dd class="col-sm-9">
                                                            <a href="{{ $job['mediaUrl'] }}" target="_blank" rel="noopener">{{ $job['mediaUrl'] }}</a>
                                                        </dd>
                                                    @endif

                                                    <dt class="col-sm-3">Created By</dt>
                                                    <dd class="col-sm-9">
                                                        {{ $userName }}
                                                        @if(!empty($job['user_email']))
                                                            <br><small class="text-muted">{{ $job['user_email'] }}</small>
                                                        @endif
                                                    </dd>

                                                    <dt class="col-sm-3">Created At</dt>
                                                    <dd class="col-sm-9">{{ $createdAt }}</dd>

                                                    @if(!empty($job['available_at_formatted']))
                                                        <dt class="col-sm-3">Available At</dt>
                                                        <dd class="col-sm-9">{{ $job['available_at_formatted'] }}</dd>
                                                    @endif

                                                    @if(!empty($job['failed_at_formatted']))
                                                        <dt class="col-sm-3">Failed At</dt>
                                                        <dd class="col-sm-9">{{ $job['failed_at_formatted'] }}</dd>
                                                    @endif

                                                    @if($status === 'pending')
                                                        <dt class="col-sm-3">Attempts</dt>
                                                        <dd class="col-sm-9">{{ $job['attempts'] ?? 0 }}</dd>
                                                    @endif

                                                    @if(!empty($job['exception']))
                                                        <dt class="col-sm-3">Exception</dt>
                                                        <dd class="col-sm-9">
                                                            <pre class="mb-0 text-danger" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto; font-size: 0.875rem;">{{ $job['exception'] }}</pre>
                                                        </dd>
                                                    @endif

                                                    @if(!empty($job['error']))
                                                        <dt class="col-sm-3">Error</dt>
                                                        <dd class="col-sm-9">
                                                            <pre class="mb-0 text-danger" style="white-space: pre-wrap;">{{ $job['error'] }}</pre>
                                                        </dd>
                                                    @endif
                                                </dl>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-ghost-strong" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3 mb-0">No jobs found.</p>
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
