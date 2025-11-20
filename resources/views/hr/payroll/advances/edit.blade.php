@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Edit Staff Advance</h2>
      <small class="text-muted">Update advance loan details</small>
    </div>
    <a href="{{ route('hr.payroll.advances.show', $advance->id) }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('hr.payroll.advances.update', $advance->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Staff</label>
            <input type="text" class="form-control" value="{{ $advance->staff->name }}" disabled>
            <div class="form-text">Staff cannot be changed</div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Advance Amount (Ksh) <span class="text-danger">*</span></label>
            <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $advance->amount) }}" required>
            @error('amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Advance Date <span class="text-danger">*</span></label>
            <input type="date" name="advance_date" class="form-control @error('advance_date') is-invalid @enderror" value="{{ old('advance_date', $advance->advance_date->format('Y-m-d')) }}" required>
            @error('advance_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Purpose</label>
            <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose', $advance->purpose) }}">
            @error('purpose')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Repayment Method <span class="text-danger">*</span></label>
            <select name="repayment_method" id="repayment_method" class="form-select @error('repayment_method') is-invalid @enderror" required>
              <option value="lump_sum" @selected(old('repayment_method', $advance->repayment_method)==='lump_sum')>Lump Sum</option>
              <option value="installments" @selected(old('repayment_method', $advance->repayment_method)==='installments')>Installments</option>
              <option value="monthly_deduction" @selected(old('repayment_method', $advance->repayment_method)==='monthly_deduction')>Monthly Deduction</option>
            </select>
            @error('repayment_method')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3" id="installment_count_field" style="display: none;">
            <label class="form-label">Number of Installments</label>
            <input type="number" name="installment_count" min="1" class="form-control @error('installment_count') is-invalid @enderror" value="{{ old('installment_count', $advance->installment_count) }}">
            @error('installment_count')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3" id="monthly_deduction_field" style="display: none;">
            <label class="form-label">Monthly Deduction Amount (Ksh)</label>
            <input type="number" name="monthly_deduction_amount" step="0.01" min="0.01" class="form-control @error('monthly_deduction_amount') is-invalid @enderror" value="{{ old('monthly_deduction_amount', $advance->monthly_deduction_amount) }}">
            @error('monthly_deduction_amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Expected Completion Date</label>
            <input type="date" name="expected_completion_date" class="form-control @error('expected_completion_date') is-invalid @enderror" value="{{ old('expected_completion_date', $advance->expected_completion_date?->format('Y-m-d')) }}">
            @error('expected_completion_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $advance->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $advance->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('hr.payroll.advances.show', $advance->id) }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Update Advance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('repayment_method').addEventListener('change', function() {
  const method = this.value;
  const installmentField = document.getElementById('installment_count_field');
  const monthlyField = document.getElementById('monthly_deduction_field');
  
  installmentField.style.display = method === 'installments' ? 'block' : 'none';
  monthlyField.style.display = method === 'monthly_deduction' ? 'block' : 'none';
});

// Trigger on page load
document.getElementById('repayment_method').dispatchEvent(new Event('change'));
</script>
@endsection

