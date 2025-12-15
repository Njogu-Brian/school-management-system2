@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Uniform Management</h1>
            <p class="text-muted mb-0">Manage uniforms, sizes, stock, and backorders</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('pos.uniforms.backorders') }}" class="btn btn-warning">
                <i class="bi bi-exclamation-triangle"></i> View Backorders ({{ $backorderedOrders->count() }})
            </a>
            <a href="{{ route('pos.products.create') }}?type=uniform" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Uniform
            </a>
        </div>
    </div>

    @include('partials.alerts')

    @if($backorderedOrders->count() > 0)
        <div class="alert alert-warning mb-4">
            <h5><i class="bi bi-exclamation-triangle"></i> Active Backorders</h5>
            <p class="mb-2">You have {{ $backorderedOrders->count() }} orders with backordered items. <a href="{{ route('pos.uniforms.backorders') }}">View all backorders</a></p>
            <div class="row g-2">
                @foreach($backorderedOrders->take(5) as $order)
                    <div class="col-md-6">
                        <div class="card card-body p-2">
                            <small>
                                <strong>{{ $order->order_number }}</strong> - 
                                @if($order->student)
                                    {{ $order->student->first_name }} {{ $order->student->last_name }}
                                @else
                                    Guest Order
                                @endif
                            </small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Uniform</th>
                            <th>Sizes Available</th>
                            <th>Total Stock</th>
                            <th>Low Stock Sizes</th>
                            <th>Backorders</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uniforms as $uniform)
                            @php
                                $variants = $uniform->variants;
                                $totalStock = $variants->sum('stock_quantity');
                                $lowStockSizes = $variants->filter(function($v) {
                                    return $v->stock_quantity <= 5 && $v->stock_quantity > 0;
                                });
                                $outOfStockSizes = $variants->filter(function($v) {
                                    return $v->stock_quantity <= 0;
                                });
                                $backorderCount = \App\Models\Pos\OrderItem::where('product_id', $uniform->id)
                                    ->where('fulfillment_status', 'backordered')
                                    ->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($uniform->images && count($uniform->images) > 0)
                                            <img src="{{ asset('storage/' . $uniform->images[0]) }}" alt="{{ $uniform->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        @endif
                                        <div>
                                            <a href="{{ route('pos.uniforms.show', $uniform) }}" class="fw-semibold text-decoration-none">
                                                {{ $uniform->name }}
                                            </a>
                                            <div class="small text-muted">KES {{ number_format($uniform->base_price, 2) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($variants as $variant)
                                            <span class="badge {{ $variant->stock_quantity > 0 ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $variant->value }} ({{ $variant->stock_quantity }})
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ number_format($totalStock) }}</strong>
                                </td>
                                <td>
                                    @if($lowStockSizes->count() > 0)
                                        <span class="badge bg-warning text-dark">
                                            {{ $lowStockSizes->count() }} sizes
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($backorderCount > 0)
                                        <span class="badge bg-danger">{{ $backorderCount }} items</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('pos.uniforms.show', $uniform) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('pos.uniforms.manage-sizes', $uniform) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-list-ul"></i> Sizes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No uniforms found</p>
                                    <a href="{{ route('pos.products.create') }}?type=uniform" class="btn btn-primary btn-sm">Add First Uniform</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($uniforms->hasPages())
            <div class="card-footer">
                {{ $uniforms->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

