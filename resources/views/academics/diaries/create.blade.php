@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Diary Entry</h1>

    <form method="POST" action="{{ route('diaries.store') }}">
        @csrf
        <div class="mb-3">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control" required>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Week</label>
            <input type="text" name="week" class="form-control" placeholder="e.g. Week 1" required>
        </div>

        <div class="mb-3">
            <label>Activities</label>
            <textarea name="activities" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label>Announcements</label>
            <textarea name="announcements" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
