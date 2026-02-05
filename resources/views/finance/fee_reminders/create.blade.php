@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h1 class="h4 mb-0">Create Fee Reminder</h1>
        <a href="{{ route('finance.fee-reminders.index') }}" class="btn btn-finance btn-finance-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-reminders.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'student_id',
                            'displayInputId' => 'feeReminderStudent',
                            'resultsId' => 'feeReminderStudentResults',
                            'placeholder' => 'Type name or admission #',
                            'inputClass' => 'form-control' . ($errors->has('student_id') ? ' is-invalid' : ''),
                            'initialLabel' => old('student_id') ? optional(\App\Models\Student::find(old('student_id')))->search_display : '',
                        ])
                        @error('student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Channel <span class="text-danger">*</span></label>
                        <select name="channel" class="form-select @error('channel') is-invalid @enderror" required>
                            <option value="email" {{ old('channel') == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="sms" {{ old('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                            <option value="both" {{ old('channel') == 'both' ? 'selected' : '' }}>Both</option>
                        </select>
                        @error('channel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                               value="{{ old('due_date') }}" required>
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Days Before Due <span class="text-danger">*</span></label>
                        <input type="number" name="days_before_due" class="form-control @error('days_before_due') is-invalid @enderror" 
                               value="{{ old('days_before_due', 7) }}" min="0" max="365" required>
                        @error('days_before_due')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Custom Message (Optional)</label>
                    <textarea name="message" class="form-control" rows="4" 
                              placeholder="Leave empty to use default message">{{ old('message') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('finance.fee-reminders.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Create Reminder</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
@endsection

