<!-- index.blade.php for receipts -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Receipts',
        'icon' => 'bi bi-receipt',
        'subtitle' => 'View and manage payment receipts'
    ])

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
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
        </div>
    </div>
</div>
@endsection
