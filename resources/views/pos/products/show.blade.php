@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.products.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h4 mb-1">{{ $product->name }}</h1>
                            <p class="text-muted mb-0">
                                <span class="badge bg-info">{{ ucfirst($product->type) }}</span>
                                @if($product->category)
                                    <span class="badge bg-secondary">{{ $product->category }}</span>
                                @endif
                                @if($product->is_featured)
                                    <span class="badge bg-primary">Featured</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('pos.products.edit', $product) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>

                    @if($product->images && count($product->images) > 0)
                        <div class="mb-3">
                            <div class="row g-2">
                                @foreach($product->images as $image)
                                    <div class="col-3">
                                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $product->name }}" class="img-fluid rounded">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <strong>SKU:</strong> {{ $product->sku ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Barcode:</strong> {{ $product->barcode ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Base Price:</strong> KES {{ number_format($product->base_price, 2) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Cost Price:</strong> {{ $product->cost_price ? 'KES ' . number_format($product->cost_price, 2) : '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Stock Quantity:</strong> 
                            @if($product->track_stock)
                                {{ number_format($product->stock_quantity) }}
                                @if($product->isLowStock())
                                    <span class="badge bg-warning">Low Stock</span>
                                @endif
                            @else
                                <span class="text-muted">Not Tracked</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Min. Stock Level:</strong> {{ $product->track_stock ? number_format($product->min_stock_level) : '—' }}
                        </div>
                        @if($product->brand)
                            <div class="col-md-6">
                                <strong>Brand:</strong> {{ $product->brand }}
                            </div>
                        @endif
                        @if($product->description)
                            <div class="col-12">
                                <strong>Description:</strong>
                                <p class="mb-0">{{ $product->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($product->variants->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Product Variants</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Value</th>
                                        <th>Type</th>
                                        <th class="text-end">Price Adjustment</th>
                                        <th class="text-end">Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->variants as $variant)
                                        <tr>
                                            <td>{{ $variant->name }}</td>
                                            <td>{{ $variant->value }}</td>
                                            <td><span class="badge bg-secondary">{{ $variant->variant_type }}</span></td>
                                            <td class="text-end">{{ $variant->price_adjustment >= 0 ? '+' : '' }}KES {{ number_format($variant->price_adjustment, 2) }}</td>
                                            <td class="text-end">{{ number_format($variant->stock_quantity) }}</td>
                                            <td>
                                                @if($variant->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                                @if($variant->is_default)
                                                    <span class="badge bg-primary">Default</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pos.products.adjust-stock', $product) }}" method="POST" class="mb-3">
                        @csrf
                        <label class="form-label">Adjust Stock</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
                            <button class="btn btn-primary" type="submit">Adjust</button>
                        </div>
                        <small class="text-muted">Use negative numbers to decrease stock</small>
                    </form>

                    <div class="d-grid gap-2">
                        <a href="{{ route('pos.products.variants.index', $product) }}" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul"></i> Manage Variants
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Product Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> 
                        @if($product->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </p>
                    <p><strong>Stock Tracking:</strong> {{ $product->track_stock ? 'Yes' : 'No' }}</p>
                    <p><strong>Backorders:</strong> {{ $product->allow_backorders ? 'Allowed' : 'Not Allowed' }}</p>
                    <p><strong>Created:</strong> {{ $product->created_at->format('M d, Y') }}</p>
                    <p><strong>Updated:</strong> {{ $product->updated_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



