@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Import Invoices / Adjustments</h3>

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.invoices.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label>Upload Excel File (.xlsx)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx" required>
        </div>

        <button class="btn btn-primary">Upload</button>
    </form>
</div>
@endsection
