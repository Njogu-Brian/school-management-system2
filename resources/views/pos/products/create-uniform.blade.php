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
                <h1>Add Uniform</h1>
                <p>Manual POS products are uniforms only. Sizes and colour are optional; classes and selling price are required.</p>
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
                        <label class="form-label">Colour <span class="text-muted">(optional)</span></label>
                        <input type="text" name="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color') }}" placeholder="e.g. Grey, Black, White">
                        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Selling Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="base_price" class="form-control @error('base_price') is-invalid @enderror" required value="{{ old('base_price') }}">
                        @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buying Price (KES) <span class="text-muted">(optional)</span></label>
                        <input type="number" step="0.01" min="0" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price') }}">
                        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Images</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Classes <span class="text-danger">*</span></label>
                        <p class="small text-muted mb-2">Select every class this uniform applies to.</p>
                        @error('classroom_ids') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <div class="row g-2">
                            @foreach($classrooms as $classroom)
                                <div class="col-md-3 col-sm-4 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="classroom_ids[]" id="class{{ $classroom->id }}" value="{{ $classroom->id }}"
                                            @checked(collect(old('classroom_ids', []))->contains($classroom->id))>
                                        <label class="form-check-label" for="class{{ $classroom->id }}">{{ $classroom->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Sizes <span class="text-muted">(optional)</span></label>
                        <p class="small text-muted mb-2">Leave blank if this item has no sizes. Enter one size per line or comma-separated.</p>
                        <textarea name="sizes_text" rows="3" class="form-control @error('sizes_text') is-invalid @enderror" placeholder="e.g. 20, 22, 24, 26&#10;or&#10;XS&#10;S&#10;M">{{ old('sizes_text') }}</textarea>
                        @error('sizes_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="track_stock" id="trackStock" value="1" @checked(old('track_stock', true))>
                            <label class="form-check-label" for="trackStock">Track stock</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="isActive">Active (available in shop)</label>
                        </div>
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
