@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Detail</div>
                <h1>{{ $item->name }}</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-settings-primary"><i class="bi bi-pencil"></i> Edit</a>
                <a href="{{ route('inventory.items.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Inventory</a>
            </div>
        </div>

        @include('partials.alerts')

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="settings-card">
                    <div class="card-body">
                        <p class="text-muted mb-3">{{ $item->description ?? 'No description provided.' }}</p>
                        <dl class="row mb-0">
                            <dt class="col-5">Category</dt><dd class="col-7">{{ $item->category ?? '—' }}</dd>
                            <dt class="col-5">Brand</dt><dd class="col-7">{{ $item->brand ?? '—' }}</dd>
                            <dt class="col-5">Quantity</dt><dd class="col-7">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</dd>
                            <dt class="col-5">Min. Level</dt><dd class="col-7">{{ number_format($item->min_stock_level, 2) }} {{ $item->unit }}</dd>
                            <dt class="col-5">Unit Cost</dt><dd class="col-7">{{ $item->unit_cost ? number_format($item->unit_cost, 2) : '—' }}</dd>
                            <dt class="col-5">Location</dt><dd class="col-7">{{ $item->location ?? '—' }}</dd>
                            <dt class="col-5">Status</dt>
                            <dd class="col-7">
                                <span class="pill-badge">{{ $item->isLowStock() ? 'Low stock' : 'Healthy' }}</span>
                                @unless($item->is_active) <span class="input-chip">Inactive</span> @endunless
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="settings-card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Adjust Stock</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inventory.items.adjust-stock', $item) }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Movement Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="in">Receive (add)</option>
                                    <option value="out">Issue (remove)</option>
                                    <option value="adjustment">Set exact quantity</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Quantity</label>
                                <input type="number" step="0.01" min="0" name="quantity" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Received from supplier, issued to class 5, etc."></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-settings-primary w-100" type="submit">
                                    <i class="bi bi-arrow-repeat"></i> Update Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Movements</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
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
                                            <span class="pill-badge">
                                                @if($transaction->type === 'in') In
                                                @elseif($transaction->type === 'out') Out
                                                @else Adjustment @endif
                                            </span>
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
</div>
@endsection

