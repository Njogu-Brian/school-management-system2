<!-- index.blade.php for payments -->
@extends('layouts.app')

@section('content')
<h1>All Payments</h1>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Method</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $payment)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $payment->student->full_name ?? 'N/A' }}</td>
                <td>KES {{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->date }}</td>
                <td>{{ ucfirst($payment->method) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
