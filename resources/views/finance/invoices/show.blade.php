@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Invoice #{{ $invoice->invoice_number }}</h3>

    @include('finance.invoices.partials.alerts')

    <p><strong>Student:</strong> {{ $invoice->student->full_name }}</p>
    <p><strong>Class:</strong> {{ $invoice->student->classrooms->name }}</p>
    <p><strong>Term:</strong> Term {{ $invoice->term }} - {{ $invoice->year }}</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Votehead</th>
                <th>Amount (Ksh)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->votehead->name ?? '-' }}</td>
                    <td>{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="table-info">
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($invoice->total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <a href="#" onclick="window.print()" class="btn btn-secondary mt-3">🖨️ Print</a>
</div>
@endsection
