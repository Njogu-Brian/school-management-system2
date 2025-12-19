@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Edit</div>
                <h1>Edit {{ $item->name }}</h1>
            </div>
            <a href="{{ route('inventory.items.show', $item) }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Item
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.items.update', $item) }}" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $item->name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <input list="categorySuggestions" name="category" class="form-control" value="{{ old('category', $item->category) }}">
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
                        <input type="text" name="brand" class="form-control" value="{{ old('brand', $item->brand) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unit of Measure</label>
                        <input type="text" name="unit" class="form-control" required value="{{ old('unit', $item->unit) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity on Hand</label>
                        <input type="number" step="0.01" min="0" name="quantity" class="form-control" required value="{{ old('quantity', $item->quantity) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" step="0.01" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', $item->min_stock_level) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" value="{{ old('unit_cost', $item->unit_cost) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Storage Location</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location', $item->location) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $item->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="isActiveCheck" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActiveCheck">Item is active / in use</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.items.show', $item) }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check2-circle"></i> Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

