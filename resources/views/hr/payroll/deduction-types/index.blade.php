@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Deduction Types</div>
                <h1 class="mb-1">Deduction Types</h1>
                <p class="text-muted mb-0">Manage deduction type definitions.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.payroll.deduction-types.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Deduction Type
                </a>
                @if($types->total())
                    <span class="pill-badge pill-secondary">{{ $types->total() }} types</span>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Deduction Types</h5>
                    <p class="mb-0 text-muted small">Statutory and custom deduction definitions.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($types->total())
                        <span class="input-chip">{{ $types->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Clickable rows</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Calculation Method</th>
                                <th>Default Amount/Percentage</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($types as $type)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $type->name }}</div>
                                        @if($type->description)
                                            <div class="small text-muted">{{ Str::limit($type->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type->code)
                                            <span class="pill-badge pill-secondary">{{ $type->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ ucfirst(str_replace('_', ' ', $type->calculation_method)) }}</span>
                                    </td>
                                    <td>
                                        @if($type->calculation_method === 'fixed_amount')
                                            <strong>Ksh {{ number_format($type->default_amount ?? 0, 2) }}</strong>
                                        @elseif($type->percentage)
                                            <strong>{{ number_format($type->percentage, 2) }}%</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type->is_statutory)
                                            <span class="pill-badge pill-danger">Statutory</span>
                                        @else
                                            <span class="pill-badge pill-primary">Custom</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="pill-badge {{ $type->is_active ? 'pill-success' : 'pill-secondary' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.deduction-types.show', $type->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="{{ route('hr.payroll.deduction-types.edit', $type->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            @if(!$type->is_statutory)
                                                <form action="{{ route('hr.payroll.deduction-types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this deduction type?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No deduction types found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($types->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $types->firstItem() }}–{{ $types->lastItem() }} of {{ $types->total() }} types
                    </div>
                    {{ $types->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

