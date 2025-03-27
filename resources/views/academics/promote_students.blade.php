@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Promote Students</h1>
    
    <form action="#" method="POST">
        @csrf
        <div class="mb-3">
            <label>Select Class to Promote From</label>
            <select class="form-control" name="current_class_id">
                <option value="1">Class A</option>
                <option value="2">Class B</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Select Class to Promote To</label>
            <select class="form-control" name="new_class_id">
                <option value="3">Class C</option>
                <option value="4">Class D</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Promote Students</button>
    </form>
</div>
@endsection
