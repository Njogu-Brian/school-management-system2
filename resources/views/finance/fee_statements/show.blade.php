<!-- show.blade.php for fee_statements -->
@extends('layouts.app')

@section('content')
<h1>Fee Statement</h1>

<p><strong>Student:</strong> {{ $student->full_name }}</p>

<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Transaction</th>
            <th>Reference</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($statement as $entry)
            <tr>
                <td>{{ $entry['date'] }}</td>
                <td>{{ $entry['description'] }}</td>
                <td>{{ $entry['reference'] }}</td>
                <td>{{ $entry['debit'] }}</td>
                <td>{{ $entry['credit'] }}</td>
                <td>{{ $entry['balance'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
