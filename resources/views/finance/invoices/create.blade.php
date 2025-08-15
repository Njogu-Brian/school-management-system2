@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Generate Invoices</h3>

    @include('finance.invoices.partials.alerts')

    <form method="POST" action="{{ route('finance.invoices.generate') }}">
        @csrf
        <div class="mb-3">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control" required>
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Year</label>
            <input type="number" name="year" value="{{ date('Y') }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Term</label>
            <select name="term" class="form-control" required>
                <option value="1">Term 1</option>
                <option value="2">Term 2</option>
                <option value="3">Term 3</option>
            </select>
        </div>

        <button class="btn btn-primary">Generate</button>
    </form>
</div>
@endsection
