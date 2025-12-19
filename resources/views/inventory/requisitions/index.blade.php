@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Requisitions</div>
                <h1>Requisitions</h1>
                <p>Teachers request items; admins approve and issue from the store.</p>
            </div>
            <a href="{{ route('inventory.requisitions.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> New Requisition
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
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
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Requisitions</h5>
                <span class="input-chip">{{ $requisitions->total() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern mb-0 align-middle">
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
                                <td class="text-capitalize"><span class="pill-badge">{{ $requisition->type }}</span></td>
                                <td>
                                    @switch($requisition->status)
                                        @case('approved')
                                            <span class="pill-badge">Approved</span>
                                            @break
                                        @case('fulfilled')
                                            <span class="pill-badge">Fulfilled</span>
                                            @break
                                        @case('rejected')
                                            <span class="pill-badge">Rejected</span>
                                            @break
                                        @default
                                            <span class="pill-badge">Pending</span>
                                    @endswitch
                                </td>
                                <td>{{ optional($requisition->created_at)->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.requisitions.show', $requisition) }}" class="btn btn-sm btn-ghost-strong">
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
                <div class="p-3">
                    {{ $requisitions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

