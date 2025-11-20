@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">New Custom Deduction</h2>
      <small class="text-muted">Create a new custom deduction for staff</small>
    </div>
    <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('hr.payroll.custom-deductions.store') }}" method="POST">
        @csrf

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Staff <span class="text-danger">*</span></label>
            <select name="staff_id" class="form-select @error('staff_id') is-invalid @enderror" required>
              <option value="">-- Select Staff --</option>
              @foreach($staff as $s)
                <option value="{{ $s->id }}" @selected(old('staff_id')==$s->id)>{{ $s->name }} ({{ $s->staff_id }})</option>
              @endforeach
            </select>
            @error('staff_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Deduction Type <span class="text-danger">*</span></label>
            <select name="deduction_type_id" class="form-select @error('deduction_type_id') is-invalid @enderror" required>
              <option value="">-- Select Type --</option>
              @foreach($types as $type)
                <option value="{{ $type->id }}" @selected(old('deduction_type_id')==$type->id)>{{ $type->name }}</option>
              @endforeach
            </select>
            @error('deduction_type_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Link to Advance (Optional)</label>
            <select name="staff_advance_id" class="form-select @error('staff_advance_id') is-invalid @enderror">
              <option value="">-- None --</option>
              @foreach($advances as $advance)
                <option value="{{ $advance->id }}" @selected(old('staff_advance_id')==$advance->id)>
                  {{ $advance->staff->name }} - Ksh {{ number_format($advance->balance, 2) }} remaining
                </option>
              @endforeach
            </select>
            <div class="form-text">Link this deduction to a staff advance</div>
            @error('staff_advance_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Deduction Amount (Ksh) <span class="text-danger">*</span></label>
            <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
            @error('amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Effective From <span class="text-danger">*</span></label>
            <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', date('Y-m-d')) }}" required>
            @error('effective_from')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Effective To</label>
            <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
            <div class="form-text">Leave empty for ongoing deduction</div>
            @error('effective_to')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Frequency <span class="text-danger">*</span></label>
            <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
              <option value="one_time" @selected(old('frequency')==='one_time')>One Time</option>
              <option value="monthly" @selected(old('frequency')==='monthly')>Monthly</option>
              <option value="quarterly" @selected(old('frequency')==='quarterly')>Quarterly</option>
              <option value="yearly" @selected(old('frequency')==='yearly')>Yearly</option>
            </select>
            @error('frequency')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Total Amount (Optional)</label>
            <input type="number" name="total_amount" step="0.01" min="0.01" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" id="total_amount">
            <div class="form-text">Total amount to be deducted over installments</div>
            @error('total_amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3" id="total_installments_field" style="display: none;">
            <label class="form-label">Total Installments</label>
            <input type="number" name="total_installments" min="1" class="form-control @error('total_installments') is-invalid @enderror" value="{{ old('total_installments') }}">
            <div class="form-text">Number of installments to complete</div>
            @error('total_installments')
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

          <div class="col-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Create Deduction
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('total_amount').addEventListener('input', function() {
  const totalAmount = this.value;
  const installmentsField = document.getElementById('total_installments_field');
  
  if (totalAmount) {
    installmentsField.style.display = 'block';
  } else {
    installmentsField.style.display = 'none';
  }
});
</script>
@endsection

