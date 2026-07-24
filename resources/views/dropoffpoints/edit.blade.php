@extends('layouts.app')

@section('content')
<h1>Edit Drop-Off Point</h1>

@if(($usageCount ?? 0) > 0)
    <div class="alert alert-info">
        Used by about {{ $usageCount }} student assignment(s). After changing rates, open Finance → Transport Fees,
        run <strong>Recalculate from routes</strong> for affected classes, then <strong>Post Pending Fees</strong>.
    </div>
@endif

<form action="{{ route('transport.dropoffpoints.update', $dropOffPoint->id) }}" method="POST" class="card p-3">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="name" class="form-label">Drop-Off Point Name</label>
        <input type="text" name="name" id="name" class="form-control"
               value="{{ old('name', $dropOffPoint->name) }}" required
               @if($dropOffPoint->isOwnMeans()) readonly @endif>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="two_way_amount" class="form-label">Two-way fare (KES / term)</label>
            <input type="number" step="0.01" min="0" name="two_way_amount" id="two_way_amount"
                   class="form-control"
                   value="{{ old('two_way_amount', $dropOffPoint->two_way_amount) }}"
                   @if($dropOffPoint->isOwnMeans()) readonly @endif>
            @error('two_way_amount') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="one_way_amount" class="form-label">One-way fare (KES / term)</label>
            <input type="number" step="0.01" min="0" name="one_way_amount" id="one_way_amount"
                   class="form-control"
                   value="{{ old('one_way_amount', $dropOffPoint->one_way_amount) }}"
                   @if($dropOffPoint->isOwnMeans()) readonly @endif>
            @error('one_way_amount') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Update Drop-Off Point</button>
        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection
