@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Create</div>
                <h1>Add Inventory Item</h1>
                <p>Capture stationery, food supplies, uniforms, devices or other assets.</p>
            </div>
            <a href="{{ route('inventory.items.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.items.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <input list="categorySuggestions" name="category" class="form-control" value="{{ old('category', 'Stationery') }}">
                        <datalist id="categorySuggestions">
                            <option value="Stationery">
                            <option value="Food">
                            <option value="Uniforms">
                            <option value="Laboratory">
                            <option value="ICT Equipment">
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand / Variant</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unit of Measure</label>
                        <input type="text" name="unit" class="form-control" required value="{{ old('unit', 'pcs') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity on Hand</label>
                        <input type="number" step="0.01" min="0" name="quantity" class="form-control" required value="{{ old('quantity', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" step="0.01" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit Cost (optional)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" value="{{ old('unit_cost') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Storage Location</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location', 'Main store') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Usage instructions, supplier, etc.">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.items.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check2-circle"></i> Save Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

