<!-- index.blade.php for credit_debit_adjustments -->
@extends('layouts.app')

@section('content')
<h1>Fee Adjustments (Credit/Debit Notes)</h1>

<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        @foreach($adjustments as $note)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $note->student->full_name ?? 'N/A' }}</td>
                <td>{{ ucfirst($note->type) }}</td>
                <td>KES {{ number_format($note->amount, 2) }}</td>
                <td>{{ $note->reason }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
