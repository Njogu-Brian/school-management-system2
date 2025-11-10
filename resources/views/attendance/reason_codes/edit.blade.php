@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Edit Reason Code</h2>
    <a href="{{ route('attendance.reason-codes.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <form action="{{ route('attendance.reason-codes.update', $reasonCode) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $reasonCode->code) }}" required>
            @error('code')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $reasonCode->name) }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $reasonCode->description) }}</textarea>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="requires_excuse" value="1" id="requires_excuse" @checked(old('requires_excuse', $reasonCode->requires_excuse))>
              <label class="form-check-label" for="requires_excuse">Requires Excuse</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_medical" value="1" id="is_medical" @checked(old('is_medical', $reasonCode->is_medical))>
              <label class="form-check-label" for="is_medical">Medical Leave</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $reasonCode->is_active))>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $reasonCode->sort_order) }}" min="0">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Update Reason Code
          </button>
          <a href="{{ route('attendance.reason-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

