@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">POS Orders</h1>
            <p class="text-muted mb-0">View and manage orders from the school shop</p>
        </div>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Order number, student name, admission number">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" @selected(request('payment_status') === 'pending')>Pending</option>
                    <option value="partial" @selected(request('payment_status') === 'partial')>Partial</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Order Type</label>
                <select name="order_type" class="form-select">
                    <option value="">All</option>
                    <option value="stationery" @selected(request('order_type') === 'stationery')>Stationery</option>
                    <option value="uniform" @selected(request('order_type') === 'uniform')>Uniform</option>
                    <option value="mixed" @selected(request('order_type') === 'mixed')>Mixed</option>
                </select>
            </div>
            <div class="col-md-2 text-md-end">
                <button class="btn btn-secondary me-2" type="submit">Filter</button>
                <a href="{{ route('pos.orders.index') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order Number</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th>Status</th>
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
                                    <span class="badge bg-info">{{ ucfirst($order->order_type) }}</span>
                                </td>
                                <td class="text-end">KES {{ number_format($order->total_amount, 2) }}</td>
                                <td class="text-end">KES {{ number_format($order->paid_amount, 2) }}</td>
                                <td>
                                    @if($order->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($order->status === 'processing')
                                        <span class="badge bg-primary">Processing</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="badge bg-danger">Cancelled</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($order->payment_status === 'partial')
                                        <span class="badge bg-warning">Partial</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
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
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No orders found</p>
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



