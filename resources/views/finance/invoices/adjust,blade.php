@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Add Credit/Debit Note</h3>

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.invoices.adjust') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Invoice Number</label>
            <input type="text" name="invoice_number" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Votehead</label>
            <select name="votehead_id" class="form-control" required>
                @foreach($voteheads as $votehead)
                    <option value="{{ $votehead->id }}">{{ $votehead->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Amount (use negative for Credit)</label>
            <input type="number" name="amount" class="form-control" required>
        </div>

        <button class="btn btn-primary">Adjust</button>
    </form>
</div>
@endsection
