@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam Marks</h1>

    <form method="POST" action="{{ route('academics.exam-marks.update',$mark) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>Marks</label>
            <input type="number" step="0.01" name="marks" value="{{ $mark->marks }}" class="form-control">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
