@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('inventory.items.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Inventory
        </a>
        <div class="btn-group">
            <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    @include('partials.alerts')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ $item->name }}</h2>
                    <p class="text-muted mb-4">{{ $item->description ?? 'No description provided.' }}</p>

                    <dl class="row mb-0">
                        <dt class="col-5">Category</dt>
                        <dd class="col-7">{{ $item->category ?? '—' }}</dd>

                        <dt class="col-5">Brand</dt>
                        <dd class="col-7">{{ $item->brand ?? '—' }}</dd>

                        <dt class="col-5">Quantity</dt>
                        <dd class="col-7">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</dd>

                        <dt class="col-5">Min. Level</dt>
                        <dd class="col-7">{{ number_format($item->min_stock_level, 2) }} {{ $item->unit }}</dd>

                        <dt class="col-5">Unit Cost</dt>
                        <dd class="col-7">{{ $item->unit_cost ? number_format($item->unit_cost, 2) : '—' }}</dd>

                        <dt class="col-5">Location</dt>
                        <dd class="col-7">{{ $item->location ?? '—' }}</dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @if($item->isLowStock())
                                <span class="badge bg-danger">Low stock</span>
                            @else
                                <span class="badge bg-success">Healthy</span>
                            @endif
                            @if(!$item->is_active)
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h3 class="h6 mb-3">Adjust Stock</h3>
                    <form method="POST" action="{{ route('inventory.items.adjust-stock', $item) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Movement Type</label>
                            <select name="type" class="form-select" required>
                                <option value="in">Receive (add to store)</option>
                                <option value="out">Issue (remove)</option>
                                <option value="adjustment">Set to exact quantity</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0" name="quantity" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Received from supplier, issued to class 5, etc."></textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-arrow-repeat"></i> Update Stock
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 mb-0">Recent Movements</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>User</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        @if($transaction->type === 'in')
                                            <span class="badge bg-success">In</span>
                                        @elseif($transaction->type === 'out')
                                            <span class="badge bg-warning text-dark">Out</span>
                                        @else
                                            <span class="badge bg-info text-dark">Adjustment</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($transaction->quantity, 2) }}</td>
                                    <td>{{ $transaction->user->name ?? 'System' }}</td>
                                    <td>{{ $transaction->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No stock movements yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

