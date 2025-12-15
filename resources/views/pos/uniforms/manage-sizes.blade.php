@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.uniforms.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Uniforms
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Manage Sizes - {{ $uniform->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pos.uniforms.update-size-stock', $uniform) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Price Adjustment</th>
                                        <th>Current Stock</th>
                                        <th>New Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($uniform->variants as $variant)
                                        <tr>
                                            <td>
                                                <strong>{{ $variant->value }}</strong>
                                                @if($variant->is_default)
                                                    <span class="badge bg-primary">Default</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $variant->price_adjustment >= 0 ? '+' : '' }}KES {{ number_format($variant->price_adjustment, 2) }}
                                                <div class="small text-muted">Total: KES {{ number_format($uniform->base_price + $variant->price_adjustment, 2) }}</div>
                                            </td>
                                            <td>
                                                <span class="{{ $variant->stock_quantity <= 0 ? 'text-danger' : ($variant->stock_quantity <= 5 ? 'text-warning' : 'text-success') }}">
                                                    {{ number_format($variant->stock_quantity) }}
                                                </span>
                                            </td>
                                            <td>
                                                <input type="hidden" name="variants[{{ $loop->index }}][id]" value="{{ $variant->id }}">
                                                <input type="number" name="variants[{{ $loop->index }}][stock_quantity]" 
                                                       class="form-control" min="0" 
                                                       value="{{ $variant->stock_quantity }}" required>
                                            </td>
                                            <td>
                                                @if($variant->stock_quantity <= 0)
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @elseif($variant->stock_quantity <= 5)
                                                    <span class="badge bg-warning">Low Stock</span>
                                                @else
                                                    <span class="badge bg-success">In Stock</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('pos.uniforms.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle"></i> Update Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('pos.products.variants.index', $uniform) }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Add New Size
                    </a>
                    <a href="{{ route('pos.uniforms.show', $uniform) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-eye"></i> View Uniform Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

