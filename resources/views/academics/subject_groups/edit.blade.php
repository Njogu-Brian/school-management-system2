@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Subject Group</h1>

    <form method="POST" action="{{ route('academics.subject_groups.update', $subject_group->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="{{ $subject_group->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control">{{ $subject_group->description }}</textarea>
        </div>

        <button class="btn btn-primary">Update</button>
    </form>

</div>
@endsection
