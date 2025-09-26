@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Subject</h1>

    <form method="POST" action="{{ route('subjects.update',$subject) }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Code</label>
            <input type="text" name="code" value="{{ $subject->code }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="{{ $subject->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Group</label>
            <select name="subject_group_id" class="form-control">
                <option value="">-- None --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ $subject->subject_group_id == $group->id ? 'selected' : '' }}>
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Learning Area</label>
            <input type="text" name="learning_area" value="{{ $subject->learning_area }}" class="form-control">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
