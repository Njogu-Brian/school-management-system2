<!-- create.blade.php for credit_debit_adjustments -->
@extends('layouts.app')

@section('content')
<h1>New Fee Adjustment</h1>

<form method="POST" action="{{ route('fee-adjustments.store') }}">
    @csrf

    <div class="mb-3">
        <label>Student</label>
        <select name="student_id" class="form-control" required>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Type</label>
        <select name="type" class="form-control" required>
            <option value="credit">Credit Note</option>
            <option value="debit">Debit Note</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Amount</label>
        <input type="number" name="amount" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Reason</label>
        <textarea name="reason" class="form-control" rows="3" required></textarea>
    </div>

    <button class="btn btn-primary">Create Adjustment</button>
</form>
@endsection
