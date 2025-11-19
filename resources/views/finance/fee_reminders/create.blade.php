@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Fee Reminder</h1>
        <a href="{{ route('finance.fee-reminders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('finance.fee-reminders.store') }}" method="POST">
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
                    <a href="{{ route('finance.fee-reminders.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

