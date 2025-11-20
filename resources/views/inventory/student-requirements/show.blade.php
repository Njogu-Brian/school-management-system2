@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('inventory.student-requirements.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to tracker
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">{{ $requirement->student->getNameAttribute() }}</h2>
                    <p class="text-muted mb-2">{{ $requirement->student->classroom->name ?? '—' }}</p>

                    <dl class="row mb-0">
                        <dt class="col-5">Requirement</dt>
                        <dd class="col-7">{{ $requirement->requirementTemplate->requirementType->name }}</dd>

                        <dt class="col-5">Academic Year</dt>
                        <dd class="col-7">{{ $requirement->academicYear->year ?? '—' }}</dd>

                        <dt class="col-5">Term</dt>
                        <dd class="col-7">{{ $requirement->term->name ?? '—' }}</dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @if($requirement->status === 'complete')
                                <span class="badge bg-success">Complete</span>
                            @elseif($requirement->status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </dd>

                        <dt class="col-5">Collected By</dt>
                        <dd class="col-7">{{ $requirement->collectedBy->name ?? '—' }}</dd>

                        <dt class="col-5">Collected At</dt>
                        <dd class="col-7">{{ optional($requirement->collected_at)->format('d M Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h3 class="h6">Parent Notification</h3>
                    @if($requirement->notified_parent)
                        <p class="text-success mb-0">✅ Parent/guardian was notified after the last update.</p>
                    @else
                        <p class="text-muted mb-0">No notification has been sent yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted">Progress</h3>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Collected {{ number_format($requirement->quantity_collected, 2) }} {{ $requirement->requirementTemplate->unit }}</span>
                            <span>Target {{ number_format($requirement->quantity_required, 2) }} {{ $requirement->requirementTemplate->unit }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php
                                $progress = min(100, ($requirement->quantity_required > 0)
                                    ? ($requirement->quantity_collected / $requirement->quantity_required) * 100
                                    : 0);
                            @endphp
                            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="row text-center mb-4">
                        <div class="col">
                            <div class="fw-semibold">{{ number_format($requirement->quantity_required, 2) }}</div>
                            <div class="text-muted small">Required</div>
                        </div>
                        <div class="col">
                            <div class="fw-semibold">{{ number_format($requirement->quantity_collected, 2) }}</div>
                            <div class="text-muted small">Collected</div>
                        </div>
                        <div class="col">
                            <div class="fw-semibold">{{ number_format(max($requirement->quantity_missing, 0), 2) }}</div>
                            <div class="text-muted small">Missing</div>
                        </div>
                    </div>

                    <h3 class="h6 text-uppercase text-muted">Notes</h3>
                    <p class="mb-0">{{ $requirement->notes ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

