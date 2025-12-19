@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory</div>
                <h1>Inventory & Assets</h1>
                <p>Track stationery, food supplies, uniforms, devices and other assets.</p>
            </div>
            <a href="{{ route('inventory.items.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> Add Item
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
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Item name, brand or category">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" value="1" name="low_stock" id="lowStockCheck" {{ request('low_stock') ? 'checked' : '' }}>
                            <label class="form-check-label" for="lowStockCheck">Low stock only</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('inventory.items.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items</h5>
                <span class="input-chip">{{ $items->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Min. Level</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('inventory.items.show', $item) }}" class="fw-semibold text-decoration-none">
                                            {{ $item->name }}
                                        </a>
                                        <div class="small text-muted">{{ $item->unit }}</div>
                                    </td>
                                    <td>{{ $item->category ?? '—' }}</td>
                                    <td>{{ $item->brand ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->min_stock_level, 2) }}</td>
                                    <td>
                                        @if($item->isLowStock())
                                            <span class="pill-badge">Low</span>
                                        @else
                                            <span class="pill-badge">OK</span>
                                        @endif
                                        @unless($item->is_active)
                                            <span class="input-chip">Inactive</span>
                                        @endunless
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('inventory.items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No inventory items found. Start by adding stationery, food supplies or assets.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($items->hasPages())
                    <div class="p-3">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

