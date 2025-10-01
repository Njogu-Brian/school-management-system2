@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam</h1>

    <form action="{{ route('academics.exams.update', $exam) }}" method="POST">
        @csrf @method('PUT')
        @include('academics.exams.partials.form', ['exam' => $exam])
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
