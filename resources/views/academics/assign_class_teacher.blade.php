@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assign Class Teacher</h1>

    <form action="#" method="POST">
        @csrf
        <div class="mb-3">
            <label>Class</label>
            <select class="form-control" name="class_id">
                <!-- Example Options -->
                <option value="1">Class A</option>
                <option value="2">Class B</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Teacher</label>
            <select class="form-control" name="teacher_id">
                <!-- Example Options -->
                <option value="1">Mr. John Doe</option>
                <option value="2">Ms. Jane Smith</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Assign Teacher</button>
    </form>
</div>
@endsection
