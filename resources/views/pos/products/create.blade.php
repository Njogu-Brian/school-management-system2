@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.products.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Add Product</h1>
                    <p class="text-muted">Create a new product for the school shop</p>

                    <form method="POST" action="{{ route('pos.products.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="stationery" @selected(old('type') === 'stationery')>Stationery</option>
                                    <option value="uniform" @selected(old('type') === 'uniform')>Uniform</option>
                                    <option value="other" @selected(old('type') === 'other')>Other</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}">
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}">
                                @error('barcode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <input list="categorySuggestions" name="category" class="form-control" value="{{ old('category') }}">
                                <datalist id="categorySuggestions">
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Base Price (KES) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="base_price" class="form-control @error('base_price') is-invalid @enderror" required value="{{ old('base_price', 0) }}">
                                @error('base_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cost Price (KES)</label>
                                <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="{{ old('cost_price') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" min="0" name="stock_quantity" class="form-control" required value="{{ old('stock_quantity', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Minimum Stock Level</label>
                                <input type="number" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inventory Item (Link)</label>
                                <select name="inventory_item_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($inventoryItems as $item)
                                        <option value="{{ $item->id }}" @selected(old('inventory_item_id') == $item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requirement Type (Link)</label>
                                <select name="requirement_type_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($requirementTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('requirement_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Product description, specifications, etc.">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Product Images</label>
                                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                                <small class="text-muted">You can upload multiple images. Maximum 2MB per image.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="track_stock" id="trackStock" value="1" @checked(old('track_stock', true))>
                                    <label class="form-check-label" for="trackStock">
                                        Track Stock
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="allow_backorders" id="allowBackorders" value="1" @checked(old('allow_backorders'))>
                                    <label class="form-check-label" for="allowBackorders">
                                        Allow Backorders (for out of stock items)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="isActive">
                                        Active (available in shop)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" @checked(old('is_featured'))>
                                    <label class="form-check-label" for="isFeatured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pos.products.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle"></i> Create Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



