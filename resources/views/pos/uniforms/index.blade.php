@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Uniforms</div>
                <h1>Uniform Products</h1>
                <p>Manage uniform items, sizes, and stock.</p>
            </div>
            <a href="{{ route('pos.products.create', ['type' => 'uniform']) }}" class="btn btn-settings-primary">
                                        <i class="bi bi-plus-lg"></i> Add Uniform Item
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Uniforms</h5>
                <span class="input-chip">{{ $uniforms->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Sizes</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uniforms as $uniform)
                                <tr>
                                    <td>{{ $uniform->name }}</td>
                                    <td>{{ $uniform->sizes->pluck('size')->join(', ') }}</td>
                                    <td class="text-end">KES {{ number_format($uniform->price, 2) }}</td>
                                    <td class="text-end">{{ number_format($uniform->total_stock) }}</td>
                                    <td><span class="pill-badge">{{ $uniform->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('pos.uniforms.show', $uniform) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('pos.uniforms.manage-sizes', $uniform) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-rulers"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No uniform items found</p>
                                        <a href="{{ route('pos.products.create', ['type' => 'uniform']) }}" class="btn btn-settings-primary btn-sm">Add Item</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($uniforms->hasPages())
                    <div class="p-3">
                        {{ $uniforms->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

