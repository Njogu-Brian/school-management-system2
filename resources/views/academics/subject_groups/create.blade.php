@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Subject Group</h1>

    <form method="POST" action="{{ route('subject-groups.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
