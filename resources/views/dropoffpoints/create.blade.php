@extends('layouts.app')

@section('content')
<h1>Add Drop-Off Point</h1>

<form action="{{ route('transport.dropoffpoints.store') }}" method="POST" class="card p-3">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Drop-Off Point Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="two_way_amount" class="form-label">Two-way fare (KES / term)</label>
            <input type="number" step="0.01" min="0" name="two_way_amount" id="two_way_amount"
                   class="form-control" value="{{ old('two_way_amount') }}" placeholder="e.g. 8000">
            <small class="text-muted">Charged when morning and evening use this same point.</small>
            @error('two_way_amount') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="one_way_amount" class="form-label">One-way fare (KES / term)</label>
            <input type="number" step="0.01" min="0" name="one_way_amount" id="one_way_amount"
                   class="form-control" value="{{ old('one_way_amount') }}" placeholder="e.g. 5000">
            <small class="text-muted">Used when the other leg is Own Means.</small>
            @error('one_way_amount') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Add Drop-Off Point</button>
        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection
