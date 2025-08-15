@extends('layouts.app')
@section('content')
<div class="container">
    <h3>View Invoices</h3>

    @include('finance.invoices.partials.alerts')

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice #</th>
                <th>Student</th>
                <th>Class</th>
                <th>Term</th>
                <th>Year</th>
                <th>Total</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->student->full_name ?? '-' }}</td>
                    <td>{{ $invoice->student->classroom->name ?? '-' }}</td>
                    <td>{{ $invoice->term }}</td>
                    <td>{{ $invoice->year }}</td>
                    <td>Ksh {{ number_format($invoice->total, 2) }}</td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td>
                        <a href="{{ route('finance.invoices.show', $invoice) }}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
