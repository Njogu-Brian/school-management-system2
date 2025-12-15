@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.discounts.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Discounts
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Create Discount</h1>

                    <form method="POST" action="{{ route('pos.discounts.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Discount Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Discount Code (Optional)</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g., SAVE20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="percentage" @selected(old('type') === 'percentage')>Percentage</option>
                                    <option value="fixed" @selected(old('type') === 'fixed')>Fixed Amount</option>
                                    <option value="bundle" @selected(old('type') === 'bundle')>Bundle Discount</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Value <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="value" class="form-control" required value="{{ old('value') }}" placeholder="10 for 10% or 100 for KES 100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Scope <span class="text-danger">*</span></label>
                                <select name="scope" class="form-select" required>
                                    <option value="all" @selected(old('scope') === 'all')>All Products</option>
                                    <option value="category" @selected(old('scope') === 'category')>Category</option>
                                    <option value="product" @selected(old('scope') === 'product')>Specific Products</option>
                                    <option value="class_bundle" @selected(old('scope') === 'class_bundle')>Class Bundle</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="categoryField" style="display: none;">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category') }}">
                            </div>
                            <div class="col-md-6" id="classroomField" style="display: none;">
                                <label class="form-label">Classroom</label>
                                <select name="classroom_id" class="form-select">
                                    <option value="">Select Classroom</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum Purchase Amount (KES)</label>
                                <input type="number" step="0.01" min="0" name="min_purchase_amount" class="form-control" value="{{ old('min_purchase_amount') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum Quantity</label>
                                <input type="number" min="1" name="min_quantity" class="form-control" value="{{ old('min_quantity') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Usage Limit</label>
                                <input type="number" min="1" name="usage_limit" class="form-control" value="{{ old('usage_limit') }}" placeholder="Total times discount can be used">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Per User Limit</label>
                                <input type="number" min="1" name="per_user_limit" class="form-control" value="{{ old('per_user_limit') }}" placeholder="Times per user">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pos.discounts.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Discount</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="scope"]').addEventListener('change', function() {
    const scope = this.value;
    document.getElementById('categoryField').style.display = scope === 'category' ? 'block' : 'none';
    document.getElementById('classroomField').style.display = scope === 'class_bundle' ? 'block' : 'none';
});
</script>
@endsection



