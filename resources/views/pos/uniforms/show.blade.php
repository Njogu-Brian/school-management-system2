@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Uniforms / Detail</div>
                <h1>{{ $uniform->name }}</h1>
                <p>Manage sizes, stock, and status.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pos.uniforms.manage-sizes', $uniform) }}" class="btn btn-ghost-strong"><i class="bi bi-rulers"></i> Manage Sizes</a>
                <a href="{{ route('pos.uniforms.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pill-badge">{{ $uniform->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">KES {{ number_format($uniform->price, 2) }}</div>
                            </div>
                        </div>
                        @if($uniform->description)
                            <p class="text-muted mb-3">{{ $uniform->description }}</p>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-4"><strong>Total Stock:</strong> {{ number_format($uniform->total_stock) }}</div>
                            <div class="col-md-4"><strong>Created:</strong> {{ $uniform->created_at->format('M d, Y') }}</div>
                            <div class="col-md-4"><strong>Updated:</strong> {{ $uniform->updated_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                @if($uniform->sizes->count())
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sizes</h5>
                        <span class="input-chip">{{ $uniform->sizes->count() }} options</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th class="text-end">Stock</th>
                                        <th class="text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($uniform->sizes as $size)
                                        <tr>
                                            <td>{{ $size->size }}</td>
                                            <td class="text-end">{{ number_format($size->stock_quantity) }}</td>
                                            <td class="text-end">KES {{ number_format($size->price_override ?? $uniform->price, 2) }}</td>
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
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('pos.uniforms.manage-sizes', $uniform) }}" class="btn btn-settings-primary w-100 mb-2">
                            <i class="bi bi-rulers"></i> Manage Sizes
                        </a>
                        <a href="{{ route('pos.products.show', $uniform) }}" class="btn btn-ghost-strong w-100">
                            <i class="bi bi-box"></i> View Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

