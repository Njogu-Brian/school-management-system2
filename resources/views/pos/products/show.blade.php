@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Products / Detail</div>
                <h1>{{ $product->name }}</h1>
                <p>View product details, variants, and quick actions.</p>
            </div>
            <a href="{{ route('pos.products.edit', $product) }}" class="btn btn-settings-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="pill-badge">{{ ucfirst($product->type) }}</span>
                                    @if($product->category)
                                        <span class="input-chip">{{ $product->category }}</span>
                                    @endif
                                    @if($product->is_featured)
                                        <span class="input-chip">Featured</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">KES {{ number_format($product->base_price, 2) }}</div>
                                @if($product->cost_price)
                                    <div class="small text-muted">Cost: KES {{ number_format($product->cost_price, 2) }}</div>
                                @endif
                            </div>
                        </div>

                        @if($product->images && count($product->images) > 0)
                            <div class="row g-2 mb-3">
                                @foreach($product->images as $image)
                                    <div class="col-4 col-md-3">
                                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $product->name }}" class="img-fluid rounded">
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><strong>SKU:</strong> {{ $product->sku ?? '—' }}</div>
                            <div class="col-md-6"><strong>Barcode:</strong> {{ $product->barcode ?? '—' }}</div>
                            <div class="col-md-6"><strong>Stock Quantity:</strong>
                                @if($product->track_stock)
                                    {{ number_format($product->stock_quantity) }}
                                    @if($product->isLowStock())
                                        <span class="pill-badge ms-1">Low</span>
                                    @endif
                                @else
                                    <span class="text-muted">Not Tracked</span>
                                @endif
                            </div>
                            <div class="col-md-6"><strong>Min. Stock Level:</strong> {{ $product->track_stock ? number_format($product->min_stock_level) : '—' }}</div>
                            @if($product->brand)
                                <div class="col-md-6"><strong>Brand:</strong> {{ $product->brand }}</div>
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
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product Variants</h5>
                        <span class="input-chip">{{ $product->variants->count() }} variants</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
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
                                            <td><span class="input-chip">{{ $variant->variant_type }}</span></td>
                                            <td class="text-end">{{ $variant->price_adjustment >= 0 ? '+' : '' }}KES {{ number_format($variant->price_adjustment, 2) }}</td>
                                            <td class="text-end">{{ number_format($variant->stock_quantity) }}</td>
                                            <td>
                                                <span class="pill-badge">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span>
                                                @if($variant->is_default)
                                                    <span class="input-chip">Default</span>
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
                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pos.products.adjust-stock', $product) }}" method="POST" class="mb-3">
                            @csrf
                            <label class="form-label">Adjust Stock</label>
                            <div class="input-group">
                                <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
                                <button class="btn btn-settings-primary" type="submit">Adjust</button>
                            </div>
                            <small class="text-muted">Use negative numbers to decrease stock</small>
                        </form>

                        <div class="d-grid gap-2">
                            <a href="{{ route('pos.products.variants.index', $product) }}" class="btn btn-ghost-strong">
                                <i class="bi bi-list-ul"></i> Manage Variants
                            </a>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Product Info</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> <span class="pill-badge">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></p>
                        <p><strong>Stock Tracking:</strong> {{ $product->track_stock ? 'Yes' : 'No' }}</p>
                        <p><strong>Backorders:</strong> {{ $product->allow_backorders ? 'Allowed' : 'Not Allowed' }}</p>
                        <p><strong>Created:</strong> {{ $product->created_at->format('M d, Y') }}</p>
                        <p><strong>Updated:</strong> {{ $product->updated_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

