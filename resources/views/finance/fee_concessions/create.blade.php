@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h1 class="h4 mb-0">Create Fee Concession</h1>
        <a href="{{ route('finance.fee-concessions.index') }}" class="btn btn-finance btn-finance-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-concessions.store') }}" method="POST">
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
                        <label class="form-label">Votehead (Optional)</label>
                        <select name="votehead_id" class="form-select">
                            <option value="">All Voteheads</option>
                            @foreach($voteheads as $votehead)
                                <option value="{{ $votehead->id }}" {{ old('votehead_id') == $votehead->id ? 'selected' : '' }}>
                                    {{ $votehead->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            <option value="fixed_amount" {{ old('type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="value" id="value" class="form-control @error('value') is-invalid @enderror" 
                               value="{{ old('value') }}" required>
                        <small class="text-muted" id="value-help">Enter percentage (0-100) or fixed amount</small>
                        @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" 
                               value="{{ old('reason') }}" required>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                               value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('finance.fee-concessions.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Create Concession</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>

<script>
    document.getElementById('type').addEventListener('change', function() {
        const help = document.getElementById('value-help');
        if (this.value === 'percentage') {
            help.textContent = 'Enter percentage (0-100)';
        } else {
            help.textContent = 'Enter fixed amount in KES';
        }
    });
</script>
@endsection

