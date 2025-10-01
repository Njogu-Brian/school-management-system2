@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Exam Grade</h1>

    <form action="{{ route('academics.exam-grades.store') }}" method="POST">
        @csrf
        @include('academics.exam_grades.partials.form', ['grade' => null])
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
