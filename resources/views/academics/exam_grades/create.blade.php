@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Add Exam Grade</h1>
    <form action="{{ route('academics.exam-grades.store') }}" method="POST">
        @csrf
        <div class="mb-3"><label>Exam Type*</label>
            <input type="text" name="exam_type" class="form-control" required>
        </div>
        <div class="mb-3"><label>Grade Name*</label>
            <input type="text" name="grade_name" class="form-control" required>
        </div>
        <div class="mb-3"><label>Percent From*</label>
            <input type="number" step="0.01" name="percent_from" class="form-control" required>
        </div>
        <div class="mb-3"><label>Percent Upto*</label>
            <input type="number" step="0.01" name="percent_upto" class="form-control" required>
        </div>
        <div class="mb-3"><label>Grade Point</label>
            <input type="number" step="0.01" name="grade_point" class="form-control">
        </div>
        <div class="mb-3"><label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
