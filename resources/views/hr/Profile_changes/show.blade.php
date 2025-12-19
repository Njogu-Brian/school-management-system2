@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Profile Changes</div>
                <h1 class="mb-1">Profile Change Request #{{ $change->id }}</h1>
                <p class="text-muted mb-0">Review and act on this staff change request.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="pill-badge {{ $change->status==='approved' ? 'pill-success' : ($change->status==='rejected' ? 'pill-danger' : 'pill-warning') }}">
                    {{ ucfirst($change->status) }}
                </span>
                <a href="{{ route('hr.profile_requests.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
                @if($change->status==='pending')
                    <form action="{{ route('hr.profile_requests.approve',$change->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="review_notes" value="">
                        <button class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Approve & Apply</button>
                    </form>
                    <button class="btn btn-ghost-strong text-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Staff</h5>
                                <p class="mb-0 text-muted small">{{ $change->staff?->full_name }} • {{ $change->staff?->staff_id }}</p>
                            </div>
                            <div class="text-end small text-muted">
                                Submitted by <strong>{{ $change->submitter?->name }}</strong><br>
                                {{ $change->created_at->format('d M Y, H:i') }}
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                            <span class="pill-badge pill-secondary">Requested {{ $change->created_at->diffForHumans() }}</span>
                            @if($change->reviewed_at)
                                <span class="pill-badge pill-success">Reviewed {{ $change->reviewed_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-modern table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Current</th>
                                    <th>Proposed</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach(($change->changes ?? []) as $field => $pair)
                                <tr>
                                    <td class="fw-semibold">{{ $field }}</td>
                                    <td class="text-muted">{{ $pair['old'] ?? '—' }}</td>
                                    <td>{{ $pair['new'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <small class="text-muted">
                            @if($change->reviewed_by)
                                Reviewed by <strong>{{ $change->reviewer?->name }}</strong> on {{ optional($change->reviewed_at)->format('d M Y, H:i') }}
                            @else
                                Awaiting review
                            @endif
                        </small>
                        @if($change->status!=='pending')
                            <span class="pill-badge {{ $change->status==='approved' ? 'pill-success' : 'pill-danger' }}">
                                {{ ucfirst($change->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Status</h5>
                    </div>
                    <div class="card-body">
                        @if($change->status==='pending')
                            <span class="pill-badge pill-warning">Pending</span>
                        @elseif($change->status==='approved')
                            <span class="pill-badge pill-success">Approved</span>
                        @else
                            <span class="pill-badge pill-danger">Rejected</span>
                        @endif
                        <div class="mt-3">
                            <div class="small text-muted">Submitted</div>
                            <div class="fw-semibold">{{ $change->created_at->format('d M Y, H:i') }}</div>
                        </div>
                        @if($change->reviewed_at)
                            <div class="mt-2">
                                <div class="small text-muted">Reviewed</div>
                                <div class="fw-semibold">{{ $change->reviewed_at->format('d M Y, H:i') }}</div>
                            </div>
                        @endif
                        @if($change->review_notes)
                            <div class="divider my-3"></div>
                            <div>
                                <div class="fw-bold mb-1">Review notes</div>
                                <div class="alert alert-soft border-0 mb-0">{{ $change->review_notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Reject modal --}}
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('hr.profile_requests.reject',$change->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Change Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Reason / notes (optional)</label>
                        <textarea name="review_notes" class="form-control" rows="4" placeholder="Explain why you are rejecting (optional)"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-ghost-strong" data-bs-dismiss="modal" type="button">Cancel</button>
                        <button class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
