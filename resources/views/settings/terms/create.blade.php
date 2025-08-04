@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Add Term</h4>
    <form action="{{ route('terms.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Term Name</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Term 1">
        </div>
        <div class="mb-3">
            <label>Academic Year</label>
            <select name="academic_year_id" class="form-control" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->year }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_current" class="form-check-input">
            <label class="form-check-label">Set as Current Term</label>
        </div>
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
