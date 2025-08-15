<!-- bulk.blade.php for credit_debit_adjustments -->
@extends('layouts.app')

@section('content')
<h1>Bulk Fee Adjustments</h1>

<form method="POST" action="{{ route('fee-adjustments.bulk') }}" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label>Upload Excel File (.xlsx)</label>
        <input type="file" name="file" class="form-control" accept=".xlsx" required>
    </div>

    <button type="submit" class="btn btn-info">Upload Adjustments</button>
</form>
@endsection
