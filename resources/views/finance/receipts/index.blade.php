<!-- index.blade.php for receipts -->
@extends('layouts.app')

@section('content')
<h1>Receipts</h1>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Receipt No</th>
            <th>Student</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($receipts as $receipt)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $receipt->receipt_number }}</td>
                <td>{{ $receipt->payment->student->full_name ?? 'N/A' }}</td>
                <td>KES {{ number_format($receipt->payment->amount ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
