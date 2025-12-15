@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">POS Products</h1>
            <p class="text-muted mb-0">Manage products available in the school shop (stationery, uniforms, etc.)</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('pos.products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Product
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('pos.products.template.download') }}"><i class="bi bi-download"></i> Download Template</a></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload"></i> Bulk Import</a></li>
            </ul>
        </div>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
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
            <div class="col-md-2 text-md-end">
                <button class="btn btn-secondary me-2" type="submit">Filter</button>
                <a href="{{ route('pos.products.index') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
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
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($product->type) }}</span>
                                </td>
                                <td>{{ $product->category ?? '—' }}</td>
                                <td class="text-end">KES {{ number_format($product->base_price, 2) }}</td>
                                <td class="text-end">
                                    @if($product->track_stock)
                                        {{ number_format($product->stock_quantity) }}
                                        @if($product->isLowStock())
                                            <span class="badge bg-warning ms-1">Low</span>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                    @if($product->is_featured)
                                        <span class="badge bg-primary">Featured</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('pos.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('pos.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
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
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No products found</p>
                                    <a href="{{ route('pos.products.create') }}" class="btn btn-primary btn-sm">Add First Product</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        @endif
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
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



