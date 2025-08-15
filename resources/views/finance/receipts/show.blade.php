<!-- show.blade.php for receipts -->
@extends('layouts.app')

@section('content')
<h1>Receipt #{{ $receipt->receipt_number }}</h1>

<ul class="list-group">
    <li class="list-group-item"><strong>Student:</strong> {{ $receipt->payment->student->full_name ?? 'N/A' }}</li>
    <li class="list-group-item"><strong>Amount:</strong> KES {{ number_format($receipt->payment->amount ?? 0, 2) }}</li>
    <li class="list-group-item"><strong>Date:</strong> {{ $receipt->payment->date ?? 'N/A' }}</li>
</ul>
@endsection
