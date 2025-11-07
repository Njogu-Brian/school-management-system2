@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Profile Change Request #{{ $change->id }}</h1>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    Staff: {{ $change->staff?->full_name }} <small class="text-muted">({{ $change->staff?->staff_id }})</small>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
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
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Submitted by <strong>{{ $change->submitter?->name }}</strong> on
                        {{ $change->created_at->format('d M Y, H:i') }}
                        @if($change->reviewed_by)
                            <br>
                            Reviewed by <strong>{{ $change->reviewer?->name }}</strong> on
                            {{ optional($change->reviewed_at)->format('d M Y, H:i') }}
                        @endif
                    </small>

                    <div>
                        @if($change->status==='pending')
                            <form action="{{ route('hr.profile_requests.approve',$change->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="review_notes" value="">
                                <button class="btn btn-success">Approve & Apply</button>
                            </form>

                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                        @elseif($change->status==='approved')
                            <span class="badge bg-success">Approved</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Status</div>
                <div class="card-body">
                    @if($change->status==='pending')
                        <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($change->status==='approved')
                        <span class="badge bg-success">Approved</span>
                    @else
                        <span class="badge bg-danger">Rejected</span>
                    @endif
                    @if($change->review_notes)
                        <hr>
                        <div>
                            <div class="fw-bold mb-1">Review notes</div>
                            <div class="text-muted">{{ $change->review_notes }}</div>
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
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                    <button class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
