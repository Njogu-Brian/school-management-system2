@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h1 class="h4 mb-0">Create Payment Plan</h1>
        <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-finance btn-finance-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-payment-plans.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                    {{ $student->first_name }} {{ $student->last_name }} - {{ $student->classroom->name ?? 'No Class' }}
                                </option>
                            @endforeach
                        </select>
                        @error('student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" class="form-control @error('total_amount') is-invalid @enderror" 
                               value="{{ old('total_amount') }}" required>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Number of Installments <span class="text-danger">*</span></label>
                        <input type="number" name="installment_count" class="form-control @error('installment_count') is-invalid @enderror" 
                               value="{{ old('installment_count', 3) }}" min="2" max="12" required>
                        @error('installment_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                               value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Create Payment Plan</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
@endsection

