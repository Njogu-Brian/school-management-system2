@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Orders</div>
                <h1>POS Orders</h1>
                <p>View and manage orders from the school shop.</p>
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
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('pos.orders.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Orders</h5>
                <span class="input-chip">{{ $orders->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
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
                                            {{ $order->student->full_name }}
                                            <div class="small text-muted">{{ $order->student->admission_number }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="pill-badge">{{ ucfirst($order->order_type) }}</span></td>
                                    <td class="text-end">KES {{ number_format($order->total_amount, 2) }}</td>
                                    <td class="text-end">KES {{ number_format($order->paid_amount, 2) }}</td>
                                    <td>
                                        @if($order->status === 'completed')
                                            <span class="pill-badge">Completed</span>
                                        @elseif($order->status === 'processing')
                                            <span class="pill-badge">Processing</span>
                                        @elseif($order->status === 'cancelled')
                                            <span class="pill-badge">Cancelled</span>
                                        @else
                                            <span class="pill-badge">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->payment_status === 'paid')
                                            <span class="pill-badge">Paid</span>
                                        @elseif($order->payment_status === 'partial')
                                            <span class="pill-badge">Partial</span>
                                        @else
                                            <span class="pill-badge">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('pos.orders.show', $order) }}" class="btn btn-sm btn-ghost-strong">
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
                @if($orders->hasPages())
                    <div class="p-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

