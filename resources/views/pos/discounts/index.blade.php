@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Discounts</div>
                <h1>Discounts & Promotions</h1>
                <p>Manage discounts and promotional codes for the shop.</p>
            </div>
            <a href="{{ route('pos.discounts.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> New Discount
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or code">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            <option value="percentage" @selected(request('type')=='percentage')>Percentage</option>
                            <option value="fixed" @selected(request('type')=='fixed')>Fixed Amount</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(request('status')=='active')>Active</option>
                            <option value="inactive" @selected(request('status')=='inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('pos.discounts.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Discounts</h5>
                <span class="input-chip">{{ $discounts->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Scope</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($discounts as $discount)
                                <tr>
                                    <td>{{ $discount->name }}</td>
                                    <td>
                                        @if($discount->code)
                                            <span class="input-chip">{{ $discount->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="pill-badge">{{ ucfirst($discount->type) }}</span></td>
                                    <td>
                                        @if($discount->type === 'percentage')
                                            {{ $discount->value }}%
                                        @else
                                            KES {{ number_format($discount->value, 2) }}
                                        @endif
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $discount->scope)) }}</td>
                                    <td>{{ $discount->usage_count }} / {{ $discount->usage_limit ?? '∞' }}</td>
                                    <td>
                                        <span class="pill-badge">{{ $discount->isValid() ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('pos.discounts.edit', $discount) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('pos.discounts.destroy', $discount) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this discount?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No discounts found</p>
                                        <a href="{{ route('pos.discounts.create') }}" class="btn btn-settings-primary btn-sm">Add First Discount</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($discounts->hasPages())
                    <div class="p-3">
                        {{ $discounts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

