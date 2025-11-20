@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('inventory.requisitions.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Requisitions
        </a>
        <span class="badge bg-light text-dark text-uppercase">{{ $requisition->requisition_number }}</span>
    </div>

    @include('partials.alerts')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Summary</h2>
                    <dl class="row mb-0">
                        <dt class="col-5">Requested by</dt>
                        <dd class="col-7">{{ $requisition->requestedBy->name ?? '—' }}</dd>

                        <dt class="col-5">Type</dt>
                        <dd class="col-7 text-capitalize">{{ $requisition->type }}</dd>

                        <dt class="col-5">Purpose</dt>
                        <dd class="col-7">{{ $requisition->purpose ?? '—' }}</dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @switch($requisition->status)
                                @case('approved')
                                    <span class="badge bg-info text-dark">Approved</span>
                                    @break
                                @case('fulfilled')
                                    <span class="badge bg-success">Fulfilled</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                    <div class="text-muted small">{{ $requisition->rejection_reason }}</div>
                                    @break
                                @default
                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                            @endswitch
                        </dd>

                        <dt class="col-5">Requested on</dt>
                        <dd class="col-7">{{ optional($requisition->created_at)->format('d M Y H:i') }}</dd>

                        <dt class="col-5">Approved on</dt>
                        <dd class="col-7">{{ optional($requisition->approved_at)->format('d M Y H:i') ?? '—' }}</dd>

                        <dt class="col-5">Issued on</dt>
                        <dd class="col-7">{{ optional($requisition->fulfilled_at)->format('d M Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @php
                $canApprove = auth()->user()->hasAnyRole(['Super Admin','Admin','Secretary']);
            @endphp

            @if($canApprove && $requisition->status === 'pending')
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h3 class="h6">Approve Quantities</h3>
                        <form method="POST" action="{{ route('inventory.requisitions.approve', $requisition) }}">
                            @csrf
                            <div class="mb-3">
                                <p class="small text-muted mb-2">Confirm the quantities to approve for each item.</p>
                            </div>
                            @foreach($requisition->items as $item)
                                <div class="mb-3">
                                    <label class="form-label small">{{ $item->item_name }}</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Requested: {{ $item->quantity_requested }} {{ $item->unit }}</span>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" step="0.01" min="0" class="form-control" name="items[{{ $loop->index }}][quantity_approved]" value="{{ $item->quantity_requested }}">
                                    </div>
                                </div>
                            @endforeach
                            <button class="btn btn-primary w-100">Approve</button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('inventory.requisitions.reject', $requisition) }}">
                            @csrf
                            <label class="form-label small">Reject (optional reason)</label>
                            <textarea class="form-control form-control-sm mb-2" name="rejection_reason" rows="2" required></textarea>
                            <button class="btn btn-outline-danger w-100">Reject Requisition</button>
                        </form>
                    </div>
                </div>
            @elseif($canApprove && $requisition->status === 'approved')
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h3 class="h6">Fulfil Requisition</h3>
                        <p class="small text-muted">Issues the approved quantities from inventory.</p>
                        <form method="POST" action="{{ route('inventory.requisitions.fulfill', $requisition) }}">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="bi bi-box-arrow-up-right"></i> Issue Items
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="h6 mb-3">Items Requested</h3>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Brand</th>
                                    <th class="text-end">Requested</th>
                                    <th class="text-end">Approved</th>
                                    <th class="text-end">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisition->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->item_name }}
                                            <div class="small text-muted">
                                                @if($requisition->type === 'inventory')
                                                    {{ $item->inventoryItem->name ?? 'Inventory item removed' }}
                                                @else
                                                    {{ $item->requirementType->name ?? 'Requirement type removed' }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $item->brand ?? '—' }}</td>
                                        <td class="text-end">{{ number_format($item->quantity_requested, 2) }} {{ $item->unit }}</td>
                                        <td class="text-end">{{ number_format($item->quantity_approved, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->quantity_issued, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

