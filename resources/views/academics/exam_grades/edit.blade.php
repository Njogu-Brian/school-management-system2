@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam Grade</h1>

    <form action="{{ route('academics.exam-grades.update',$exam_grade) }}" method="POST">
        @csrf @method('PUT')
        @include('academics.exam_grades.partials.form', ['grade' => $exam_grade])
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
