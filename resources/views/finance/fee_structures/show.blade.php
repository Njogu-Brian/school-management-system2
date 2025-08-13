@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Fee Structure for {{ $feeStructure->classroom->name }} - {{ $feeStructure->year }}</h3>
    <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="btn btn-warning mb-3">Edit</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Votehead</th>
                <th>Term</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feeStructure->charges as $charge)
            <tr>
                <td>{{ $charge->votehead->name }}</td>
                <td>{{ $charge->term }}</td>
                <td>{{ number_format($charge->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
