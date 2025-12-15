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
                    <h1 class="h4 mb-3">Edit Product</h1>

                    <form method="POST" action="{{ route('pos.products.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name', $product->name) }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="stationery" @selected(old('type', $product->type) === 'stationery')>Stationery</option>
                                    <option value="uniform" @selected(old('type', $product->type) === 'uniform')>Uniform</option>
                                    <option value="other" @selected(old('type', $product->type) === 'other')>Other</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}">
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <input list="categorySuggestions" name="category" class="form-control" value="{{ old('category', $product->category) }}">
                                <datalist id="categorySuggestions">
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand', $product->brand) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Base Price (KES) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="base_price" class="form-control" required value="{{ old('base_price', $product->base_price) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cost Price (KES)</label>
                                <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" min="0" name="stock_quantity" class="form-control" required value="{{ old('stock_quantity', $product->stock_quantity) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Minimum Stock Level</label>
                                <input type="number" min="0" name="min_stock_level" class="form-control" value="{{ old('min_stock_level', $product->min_stock_level) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inventory Item (Link)</label>
                                <select name="inventory_item_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($inventoryItems as $item)
                                        <option value="{{ $item->id }}" @selected(old('inventory_item_id', $product->inventory_item_id) == $item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requirement Type (Link)</label>
                                <select name="requirement_type_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($requirementTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('requirement_type_id', $product->requirement_type_id) == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                            @if($product->images && count($product->images) > 0)
                                <div class="col-12">
                                    <label class="form-label">Current Images</label>
                                    <div class="row g-2">
                                        @foreach($product->images as $image)
                                            <div class="col-2">
                                                <div class="position-relative">
                                                    <img src="{{ asset('storage/' . $image) }}" class="img-fluid rounded">
                                                    <div class="form-check position-absolute top-0 end-0 m-1">
                                                        <input class="form-check-input" type="checkbox" name="remove_images[]" value="{{ $image }}" id="remove_{{ $loop->index }}">
                                                        <label class="form-check-label text-white bg-dark rounded px-1" for="remove_{{ $loop->index }}" style="font-size: 0.7rem;">Remove</label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">Add New Images</label>
                                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                                <small class="text-muted">You can upload multiple images. Maximum 2MB per image.</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="track_stock" id="trackStock" value="1" @checked(old('track_stock', $product->track_stock))>
                                    <label class="form-check-label" for="trackStock">Track Stock</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="allow_backorders" id="allowBackorders" value="1" @checked(old('allow_backorders', $product->allow_backorders))>
                                    <label class="form-check-label" for="allowBackorders">Allow Backorders</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" @checked(old('is_featured', $product->is_featured))>
                                    <label class="form-check-label" for="isFeatured">Featured</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pos.products.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle"></i> Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



