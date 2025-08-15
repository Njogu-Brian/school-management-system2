<!-- create.blade.php for payments -->
@extends('layouts.app')

@section('content')
<h1>Record Payment</h1>

<form method="POST" action="{{ route('payments.store') }}">
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
        <label>Amount</label>
        <input type="number" name="amount" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Payment Method</label>
        <select name="method" class="form-control">
            <option value="cash">Cash</option>
            <option value="mpesa">Mpesa</option>
            <option value="bank">Bank</option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">Submit Payment</button>
</form>
@endsection
