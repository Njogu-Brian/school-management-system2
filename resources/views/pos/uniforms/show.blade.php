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
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h4 mb-1">{{ $uniform->name }}</h1>
                            <p class="text-muted mb-0">Base Price: KES {{ number_format($uniform->base_price, 2) }}</p>
                        </div>
                        <a href="{{ route('pos.products.edit', $uniform) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>

                    @if($uniform->images && count($uniform->images) > 0)
                        <div class="mb-3">
                            <div class="row g-2">
                                @foreach($uniform->images as $image)
                                    <div class="col-3">
                                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $uniform->name }}" class="img-fluid rounded">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <h5 class="mt-4 mb-3">Size Variants</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Price</th>
                                    <th class="text-end">Stock</th>
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
                                            KES {{ number_format($uniform->base_price + $variant->price_adjustment, 2) }}
                                            @if($variant->price_adjustment != 0)
                                                <div class="small text-muted">
                                                    ({{ $variant->price_adjustment >= 0 ? '+' : '' }}KES {{ number_format($variant->price_adjustment, 2) }})
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="{{ $variant->stock_quantity <= 0 ? 'text-danger' : ($variant->stock_quantity <= 5 ? 'text-warning' : 'text-success') }}">
                                                {{ number_format($variant->stock_quantity) }}
                                            </span>
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
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Student</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @foreach($order->items->where('product_id', $uniform->id) as $item)
                                        <tr>
                                            <td>
                                                <a href="{{ route('pos.orders.show', $order) }}">{{ $order->order_number }}</a>
                                            </td>
                                            <td>
                                                @if($order->student)
                                                    {{ $order->student->first_name }} {{ $order->student->last_name }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->variant_name ?? 'N/A' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>
                                                @if($item->fulfillment_status === 'fulfilled')
                                                    <span class="badge bg-success">Fulfilled</span>
                                                @elseif($item->fulfillment_status === 'backordered')
                                                    <span class="badge bg-danger">Backordered</span>
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No orders yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($orders->hasPages())
                        <div class="card-footer">
                            {{ $orders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('pos.uniforms.manage-sizes', $uniform) }}" class="btn btn-primary">
                            <i class="bi bi-list-ul"></i> Manage Sizes & Stock
                        </a>
                        <a href="{{ route('pos.products.variants.index', $uniform) }}" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Add New Size
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Uniform Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Type:</strong> Uniform</p>
                    <p><strong>Total Sizes:</strong> {{ $uniform->variants->count() }}</p>
                    <p><strong>Total Stock:</strong> {{ number_format($uniform->variants->sum('stock_quantity')) }}</p>
                    <p><strong>Backorders:</strong> 
                        {{ \App\Models\Pos\OrderItem::where('product_id', $uniform->id)->where('fulfillment_status', 'backordered')->count() }}
                    </p>
                    <p><strong>Status:</strong> 
                        @if($uniform->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

