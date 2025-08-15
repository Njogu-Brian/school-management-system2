@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Manage Fee Structures</h3>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Manage Form --}}
    <form action="{{ route('finance.fee-structures.manage') }}" method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="classroom_id" class="form-label">Select Class</label>
            <select name="classroom_id" id="classroom_id" class="form-control" required>
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Manage Structure</button>
        </div>
    </form>

    <hr>

    <h5>Replicate Fee Structure</h5>
    <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <label>Source Class</label>
                <select name="source_classroom_id" class="form-control" required>
                    <option value="">Select</option>
                    @foreach($structures as $s)
                        <option value="{{ $s->classroom_id }}">{{ $s->classroom->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label>Target Classes (hold Ctrl to select multiple)</label>
                <select name="target_classroom_ids[]" class="form-control" multiple required>
                    @foreach($classrooms as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-warning">Replicate Fee Structure</button>
        </div>
    </form>
</div>
@endsection
