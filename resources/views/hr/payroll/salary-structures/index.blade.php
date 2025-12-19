@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Salary Structures</div>
                <h1 class="mb-1">Salary Structures</h1>
                <p class="text-muted mb-0">Manage staff salary structures.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.payroll.salary-structures.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Structure
                </a>
                @if($structures->total())
                    <span class="pill-badge pill-secondary">{{ $structures->total() }} structures</span>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        {{-- Filters --}}
        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by staff and active status.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id')==$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">All</option>
                            <option value="1" @selected(request('is_active')==='1')>Active</option>
                            <option value="0" @selected(request('is_active')==='0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Salary Structures</h5>
                    <p class="mb-0 text-muted small">Compensation definitions by staff member.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($structures->total())
                        <span class="input-chip">{{ $structures->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Ongoing vs dated</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Basic Salary</th>
                                <th>Gross Salary</th>
                                <th>Net Salary</th>
                                <th>Effective Period</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($structures as $structure)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $structure->staff->name }}</div>
                                        <div class="small text-muted">{{ $structure->staff->department->name ?? '—' }}</div>
                                    </td>
                                    <td><strong>Ksh {{ number_format($structure->basic_salary, 2) }}</strong></td>
                                    <td><strong class="text-success">Ksh {{ number_format($structure->gross_salary, 2) }}</strong></td>
                                    <td><strong class="text-primary">Ksh {{ number_format($structure->net_salary, 2) }}</strong></td>
                                    <td>
                                        <div>{{ $structure->effective_from->format('M d, Y') }}</div>
                                        @if($structure->effective_to)
                                            <div class="small text-muted">to {{ $structure->effective_to->format('M d, Y') }}</div>
                                        @else
                                            <div class="small text-muted">Ongoing</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="pill-badge {{ $structure->is_active ? 'pill-success' : 'pill-secondary' }}">
                                            {{ $structure->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.salary-structures.show', $structure->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="{{ route('hr.payroll.salary-structures.edit', $structure->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No salary structures found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($structures->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $structures->firstItem() }}–{{ $structures->lastItem() }} of {{ $structures->total() }} structures
                    </div>
                    {{ $structures->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

