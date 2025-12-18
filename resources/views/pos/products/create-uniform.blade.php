@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Uniforms / Create</div>
                <h1>Add Uniform Item</h1>
                <p>Create a uniform product. Manage sizes after saving.</p>
                <div class="d-flex gap-2 flex-wrap mt-2">
                    <a href="{{ route('pos.products.create', ['type' => 'uniform']) }}" class="btn btn-settings-primary">Uniform</a>
                    <a href="{{ route('pos.products.create', ['type' => 'stationery']) }}" class="btn btn-ghost-strong">Stationery / Requirement</a>
                    <a href="{{ route('pos.products.create', ['type' => 'other']) }}" class="btn btn-ghost-strong">Other</a>
                    <a href="{{ route('pos.products.template.download') }}" class="btn btn-ghost-strong"><i class="bi bi-download"></i> Download Template</a>
                    <a href="{{ route('pos.products.index') }}#importModal" class="btn btn-ghost-strong"><i class="bi bi-upload"></i> Bulk Import</a>
                </div>
            </div>
            <a href="{{ route('pos.uniforms.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Uniforms
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form method="POST" action="{{ route('pos.products.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <input type="hidden" name="type" value="uniform">

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Uniform Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <input list="categorySuggestions" name="category" class="form-control" value="{{ old('category', 'Uniform') }}">
                        <datalist id="categorySuggestions">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}">
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}">
                        @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Base Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="base_price" class="form-control @error('base_price') is-invalid @enderror" required value="{{ old('base_price', 0) }}">
                        @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost Price (KES)</label>
                        <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="{{ old('cost_price') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', 0) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Material, fit, care instructions">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Product Images</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                        <small class="text-muted">You can upload multiple images. Maximum 2MB per image.</small>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="track_stock" id="trackStock" value="1" @checked(old('track_stock', true))>
                            <label class="form-check-label" for="trackStock">Track Stock</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="allow_backorders" id="allowBackorders" value="1" @checked(old('allow_backorders'))>
                            <label class="form-check-label" for="allowBackorders">Allow Backorders</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="isActive">Active (available in shop)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" @checked(old('is_featured'))>
                            <label class="form-check-label" for="isFeatured">Featured Product</label>
                        </div>
                    </div>

                    <div class="alert alert-info mt-2">
                        Sizes and stock per size can be managed after saving via "Manage Sizes".
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('pos.uniforms.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check2-circle"></i> Create Uniform
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

