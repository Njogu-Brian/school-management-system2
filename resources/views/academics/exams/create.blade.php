@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Exam</h1>

    <form action="{{ route('academics.exams.store') }}" method="POST">
        @csrf
        @include('academics.exams.partials.form', ['exam' => null])
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
