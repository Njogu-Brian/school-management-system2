@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.teacher-requirements.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Requirements
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Requirement Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Student:</strong>
                            <p class="mb-0">{{ $requirement->student->first_name }} {{ $requirement->student->last_name }}</p>
                            <small class="text-muted">{{ $requirement->student->admission_number }} - {{ $requirement->student->classroom->name ?? '—' }}</small>
                        </div>
                        <div class="col-md-6">
                            <strong>Requirement:</strong>
                            <p class="mb-0">{{ $requirement->requirementTemplate->requirementType->name }}</p>
                            @if($requirement->requirementTemplate->brand)
                                <small class="text-muted">Brand: {{ $requirement->requirementTemplate->brand }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Required:</strong> {{ number_format($requirement->quantity_required, 2) }} {{ $requirement->requirementTemplate->unit }}
                        </div>
                        <div class="col-md-4">
                            <strong>Collected:</strong> {{ number_format($requirement->quantity_collected, 2) }} {{ $requirement->requirementTemplate->unit }}
                        </div>
                        <div class="col-md-4">
                            <strong>Missing:</strong> 
                            <span class="{{ $requirement->quantity_missing > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format(max($requirement->quantity_missing, 0), 2) }} {{ $requirement->requirementTemplate->unit }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            @if($requirement->status === 'complete')
                                <span class="badge bg-success">Complete</span>
                            @elseif($requirement->status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Purchase Source:</strong>
                            @if($requirement->purchased_through_pos)
                                <span class="badge bg-success">
                                    <i class="bi bi-shop"></i> POS Purchase
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-bag"></i> Outside Purchase
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($requirement->posOrder)
                        <div class="alert alert-info">
                            <strong>POS Order Information:</strong><br>
                            Order Number: <a href="{{ route('pos.orders.show', $requirement->posOrder) }}">{{ $requirement->posOrder->order_number }}</a><br>
                            Order Date: {{ $requirement->posOrder->created_at->format('M d, Y H:i') }}<br>
                            Payment Status: 
                            @if($requirement->posOrder->payment_status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </div>
                    @endif

                    @if($requirement->notes)
                        <div class="mb-3">
                            <strong>Notes:</strong>
                            <p class="mb-0 text-muted">{{ $requirement->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if($requirement->status !== 'complete')
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Mark as Received</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pos.teacher-requirements.mark-received', $requirement) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Quantity Received</label>
                                <input type="number" step="0.01" min="0" max="{{ $requirement->quantity_required - $requirement->quantity_collected }}" 
                                       name="quantity_received" class="form-control" required 
                                       value="{{ $requirement->quantity_required - $requirement->quantity_collected }}">
                                <small class="text-muted">Maximum: {{ number_format($requirement->quantity_required - $requirement->quantity_collected, 2) }} {{ $requirement->requirementTemplate->unit }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Mark as Received
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection



