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
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'student_id',
                            'displayInputId' => 'studentLiveSearchFPP',
                            'resultsId' => 'studentLiveResultsFPP',
                            'placeholder' => 'Type name or admission #',
                            'initialLabel' => old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : ''
                        ])
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

