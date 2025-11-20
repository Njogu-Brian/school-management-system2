@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Inventory & Assets</h1>
            <p class="text-muted mb-0">Track stationery, food supplies, uniforms, devices and any other school assets.</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('inventory.items.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Item
            </a>
        </div>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
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
                    <label class="form-check-label" for="lowStockCheck">
                        Low stock only
                    </label>
                </div>
            </div>
            <div class="col-md-3 text-md-end">
                <button class="btn btn-secondary me-2" type="submit">Filter</button>
                <a href="{{ route('inventory.items.index') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
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
                                        <span class="badge bg-danger">Low</span>
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                    @if(!$item->is_active)
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('inventory.items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
        </div>
        @if($items->hasPages())
            <div class="card-footer">
                {{ $items->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

