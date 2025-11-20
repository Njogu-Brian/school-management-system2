@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Requisitions</h1>
            <p class="text-muted mb-0">Teachers request items they need, administrators approve and issue from the store.</p>
        </div>
        <a href="{{ route('inventory.requisitions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Requisition
        </a>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['pending','approved','fulfilled','rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['inventory' => 'Inventory Items','requirement' => 'Requirement Items'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Number</th>
                        <th>Requested By</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions as $requisition)
                        <tr>
                            <td class="fw-semibold">{{ $requisition->requisition_number }}</td>
                            <td>{{ $requisition->requestedBy->name ?? '—' }}</td>
                            <td class="text-capitalize">{{ $requisition->type }}</td>
                            <td>
                                @switch($requisition->status)
                                    @case('approved')
                                        <span class="badge bg-info text-dark">Approved</span>
                                        @break
                                    @case('fulfilled')
                                        <span class="badge bg-success">Fulfilled</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @break
                                    @default
                                        <span class="badge bg-warning text-dark">Pending</span>
                                @endswitch
                            </td>
                            <td>{{ optional($requisition->created_at)->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('inventory.requisitions.show', $requisition) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No requisitions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requisitions->hasPages())
            <div class="card-footer">
                {{ $requisitions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

