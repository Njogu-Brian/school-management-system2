@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Subject</h1>

    <form method="POST" action="{{ route('subjects.store') }}">
        @csrf
        <div class="mb-3">
            <label>Code</label>
            <input type="text" name="code" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Group</label>
            <select name="subject_group_id" class="form-control">
                <option value="">-- None --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Learning Area</label>
            <input type="text" name="learning_area" class="form-control">
        </div>

        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
