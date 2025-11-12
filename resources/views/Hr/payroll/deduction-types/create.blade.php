@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">New Deduction Type</h2>
      <small class="text-muted">Create a new deduction type</small>
    </div>
    <a href="{{ route('hr.payroll.deduction-types.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('hr.payroll.deduction-types.store') }}" method="POST">
        @csrf

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g., School Uniform">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g., UNIFORM">
            <div class="form-text">Unique code for this deduction type</div>
            @error('code')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Calculation Method <span class="text-danger">*</span></label>
            <select name="calculation_method" id="calculation_method" class="form-select @error('calculation_method') is-invalid @enderror" required>
              <option value="fixed_amount" @selected(old('calculation_method')==='fixed_amount')>Fixed Amount</option>
              <option value="percentage_of_basic" @selected(old('calculation_method')==='percentage_of_basic')>Percentage of Basic Salary</option>
              <option value="percentage_of_gross" @selected(old('calculation_method')==='percentage_of_gross')>Percentage of Gross Salary</option>
              <option value="custom" @selected(old('calculation_method')==='custom')>Custom</option>
            </select>
            @error('calculation_method')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3" id="default_amount_field">
            <label class="form-label">Default Amount (Ksh)</label>
            <input type="number" name="default_amount" step="0.01" min="0" class="form-control @error('default_amount') is-invalid @enderror" value="{{ old('default_amount') }}">
            @error('default_amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3" id="percentage_field" style="display: none;">
            <label class="form-label">Percentage (%)</label>
            <input type="number" name="percentage" step="0.01" min="0" max="100" class="form-control @error('percentage') is-invalid @enderror" value="{{ old('percentage') }}">
            @error('percentage')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
              <label class="form-check-label" for="is_active">
                Active
              </label>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_statutory" id="is_statutory" value="1" @checked(old('is_statutory'))>
              <label class="form-check-label" for="is_statutory">
                Statutory Deduction (NSSF, NHIF, PAYE)
              </label>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" value="1" @checked(old('requires_approval'))>
              <label class="form-check-label" for="requires_approval">
                Requires Approval
              </label>
            </div>
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('hr.payroll.deduction-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Create Deduction Type
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('calculation_method').addEventListener('change', function() {
  const method = this.value;
  const amountField = document.getElementById('default_amount_field');
  const percentageField = document.getElementById('percentage_field');
  
  if (method === 'fixed_amount' || method === 'custom') {
    amountField.style.display = 'block';
    percentageField.style.display = 'none';
    if (method === 'fixed_amount') {
      amountField.querySelector('input').required = true;
    }
  } else if (method === 'percentage_of_basic' || method === 'percentage_of_gross') {
    amountField.style.display = 'none';
    percentageField.style.display = 'block';
    percentageField.querySelector('input').required = true;
  }
});

// Trigger on page load
document.getElementById('calculation_method').dispatchEvent(new Event('change'));
</script>
@endsection

