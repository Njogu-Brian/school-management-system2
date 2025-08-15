<!-- show.blade.php for payments -->
@extends('layouts.app')

@section('content')
<h1>Payment Details</h1>

<ul class="list-group">
    <li class="list-group-item"><strong>Student:</strong> {{ $payment->student->full_name ?? 'N/A' }}</li>
    <li class="list-group-item"><strong>Amount:</strong> KES {{ number_format($payment->amount, 2) }}</li>
    <li class="list-group-item"><strong>Date:</strong> {{ $payment->date }}</li>
    <li class="list-group-item"><strong>Method:</strong> {{ ucfirst($payment->method) }}</li>
</ul>
@endsection
