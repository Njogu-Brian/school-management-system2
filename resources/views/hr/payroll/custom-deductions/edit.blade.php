@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Edit Custom Deduction</h2>
      <small class="text-muted">Update deduction details</small>
    </div>
    <a href="{{ route('hr.payroll.custom-deductions.show', $deduction->id) }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('hr.payroll.custom-deductions.update', $deduction->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Staff</label>
            <input type="text" class="form-control" value="{{ $deduction->staff->name }}" disabled>
            <div class="form-text">Staff cannot be changed</div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Deduction Type</label>
            <input type="text" class="form-control" value="{{ $deduction->deductionType->name }}" disabled>
            <div class="form-text">Deduction type cannot be changed</div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Deduction Amount (Ksh) <span class="text-danger">*</span></label>
            <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $deduction->amount) }}" required>
            @error('amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Frequency <span class="text-danger">*</span></label>
            <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
              <option value="one_time" @selected(old('frequency', $deduction->frequency)==='one_time')>One Time</option>
              <option value="monthly" @selected(old('frequency', $deduction->frequency)==='monthly')>Monthly</option>
              <option value="quarterly" @selected(old('frequency', $deduction->frequency)==='quarterly')>Quarterly</option>
              <option value="yearly" @selected(old('frequency', $deduction->frequency)==='yearly')>Yearly</option>
            </select>
            @error('frequency')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Effective From <span class="text-danger">*</span></label>
            <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $deduction->effective_from->format('Y-m-d')) }}" required>
            @error('effective_from')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Effective To</label>
            <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $deduction->effective_to?->format('Y-m-d')) }}">
            <div class="form-text">Leave empty for ongoing deduction</div>
            @error('effective_to')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $deduction->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $deduction->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('hr.payroll.custom-deductions.show', $deduction->id) }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Update Deduction
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

