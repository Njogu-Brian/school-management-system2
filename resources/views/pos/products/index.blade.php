@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Products</div>
                <h1>POS Products</h1>
                <p>Manage products available in the school shop (stationery, uniforms, etc.).</p>
            </div>
            <div class="btn-group">
                <a href="{{ route('pos.products.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-lg"></i> Add Product
                </a>
                <button type="button" class="btn btn-settings-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('pos.products.template.download') }}"><i class="bi bi-download"></i> Download Template</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload"></i> Bulk Import</a></li>
                </ul>
            </div>
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
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Product name, SKU, barcode">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
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
                                Low stock
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" value="1" name="out_of_stock" id="outOfStockCheck" {{ request('out_of_stock') ? 'checked' : '' }}>
                            <label class="form-check-label" for="outOfStockCheck">
                                Out of stock
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end d-flex gap-2">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('pos.products.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Products</h5>
                <span class="input-chip">{{ $products->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($product->images && count($product->images) > 0)
                                                <img src="{{ asset('storage/' . $product->images[0]) }}" alt="{{ $product->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="rounded me-2 bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('pos.products.show', $product) }}" class="fw-semibold text-decoration-none">
                                                    {{ $product->name }}
                                                </a>
                                                @if($product->sku)
                                                    <div class="small text-muted">SKU: {{ $product->sku }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="pill-badge">{{ ucfirst($product->type) }}</span></td>
                                    <td>{{ $product->category ?? '—' }}</td>
                                    <td class="text-end">KES {{ number_format($product->base_price, 2) }}</td>
                                    <td class="text-end">
                                        @if($product->track_stock)
                                            {{ number_format($product->stock_quantity) }}
                                            @if($product->isLowStock())
                                                <span class="pill-badge ms-1">Low</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->is_active)
                                            <span class="pill-badge">Active</span>
                                        @else
                                            <span class="pill-badge">Inactive</span>
                                        @endif
                                        @if($product->is_featured)
                                            <span class="input-chip">Featured</span>
                                        @endif
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('pos.products.edit', $product) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('pos.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
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
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No products found</p>
                                        <a href="{{ route('pos.products.create') }}" class="btn btn-settings-primary btn-sm">Add First Product</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($products->hasPages())
                    <div class="p-3">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('pos.products.bulk-import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Upload an Excel file with product data. <a href="{{ route('pos.products.template.download') }}">Download template</a></p>
                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-settings-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

