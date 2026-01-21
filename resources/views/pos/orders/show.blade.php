@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Orders / Detail</div>
                <h1>Order #{{ $order->order_number }}</h1>
                <p>Review items, fulfillment, and payments.</p>
            </div>
            @if($order->status !== 'cancelled' && $order->status !== 'completed')
                <form action="{{ route('pos.orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <button class="btn btn-ghost-strong text-danger">Cancel Order</button>
                </form>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Student:</strong>
                                @if($order->student)
                                    {{ $order->student->full_name }}
                                    <div class="small text-muted">{{ $order->student->admission_number }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Parent:</strong>
                                @if($order->parent)
                                    {{ $order->parent->first_name }} {{ $order->parent->last_name }}
                                    <div class="small text-muted">{{ $order->parent->phone }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-modern align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->product_name }}
                                                @if($item->variant_name)
                                                    <div class="small text-muted">{{ $item->variant_name }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">KES {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end">KES {{ number_format($item->total_price, 2) }}</td>
                                            <td>
                                                @if($item->fulfillment_status === 'fulfilled')
                                                    <span class="pill-badge">Fulfilled</span>
                                                @elseif($item->fulfillment_status === 'partial')
                                                    <span class="pill-badge">Partial</span>
                                                @elseif($item->fulfillment_status === 'backordered')
                                                    <span class="pill-badge">Backordered</span>
                                                @else
                                                    <span class="pill-badge">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <th class="text-end">KES {{ number_format($order->subtotal, 2) }}</th>
                                        <td></td>
                                    </tr>
                                    @if($order->discount_amount > 0)
                                        <tr>
                                            <th colspan="3" class="text-end">Discount:</th>
                                            <th class="text-end text-danger">-KES {{ number_format($order->discount_amount, 2) }}</th>
                                            <td></td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">KES {{ number_format($order->total_amount, 2) }}</th>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Paid:</th>
                                        <th class="text-end">KES {{ number_format($order->paid_amount, 2) }}</th>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Balance:</th>
                                        <th class="text-end">KES {{ number_format($order->balance, 2) }}</th>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> <span class="pill-badge">{{ ucfirst($order->status) }}</span></p>
                        <p><strong>Payment Status:</strong> <span class="pill-badge">{{ ucfirst($order->payment_status) }}</span></p>
                        <p><strong>Payment Method:</strong> {{ $order->payment_method ?? '—' }}</p>
                        <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                        @if($order->paid_at)
                            <p><strong>Paid At:</strong> {{ $order->paid_at->format('M d, Y H:i') }}</p>
                        @endif
                    </div>
                </div>

                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pos.orders.update-status', $order) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="pending" @selected($order->status === 'pending')>Pending</option>
                                    <option value="processing" @selected($order->status === 'processing')>Processing</option>
                                    <option value="completed" @selected($order->status === 'completed')>Completed</option>
                                    <option value="cancelled" @selected($order->status === 'cancelled')>Cancelled</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-settings-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

