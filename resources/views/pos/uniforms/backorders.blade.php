@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Uniform Backorders</h1>
            <p class="text-muted mb-0">Manage orders for out-of-stock uniform items</p>
        </div>
        <a href="{{ route('pos.uniforms.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Uniforms
        </a>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Order number, student name, admission number">
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-secondary me-2" type="submit">Filter</button>
                <a href="{{ route('pos.uniforms.backorders') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Student</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('pos.orders.show', $order) }}" class="fw-semibold text-decoration-none">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($order->student)
                                        {{ $order->student->first_name }} {{ $order->student->last_name }}
                                        <div class="small text-muted">{{ $order->student->admission_number }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($order->items->where('fulfillment_status', 'backordered') as $item)
                                        <div class="small">
                                            {{ $item->product_name }}
                                            @if($item->variant_name)
                                                ({{ $item->variant_name }})
                                            @endif
                                            - Qty: {{ $item->quantity - $item->quantity_fulfilled }} backordered
                                        </div>
                                    @endforeach
                                </td>
                                <td>KES {{ number_format($order->total_amount, 2) }}</td>
                                <td>
                                    @if($order->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('pos.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No backorders found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

