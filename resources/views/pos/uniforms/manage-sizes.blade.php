@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Uniforms / Sizes</div>
                <h1>Manage Sizes - {{ $uniform->name }}</h1>
                <p>Update size options, stock, and price overrides.</p>
            </div>
            <a href="{{ route('pos.uniforms.show', $uniform) }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('pos.uniforms.update-size-stock', $uniform) }}" method="POST" class="table-responsive">
                    @csrf
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">Price Override</th>
                                <th class="text-center">Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uniform->variants as $index => $variant)
                                <tr>
                                    <td>{{ $variant->name ?? $variant->size ?? 'Size' }}</td>
                                    <td class="text-end">
                                        <input type="number" name="variants[{{ $index }}][stock_quantity]" class="form-control text-end" value="{{ $variant->stock_quantity }}" required>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" step="0.01" name="variants[{{ $index }}][price_override]" class="form-control text-end" value="{{ $variant->price_adjustment ?? '' }}">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="variants[{{ $index }}][is_active]" value="1" {{ $variant->is_active ? 'checked' : '' }}>
                                    </td>
                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end align-items-center mt-3">
                        <button type="submit" class="btn btn-settings-primary">Save Sizes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

