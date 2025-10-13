@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Record Student Behaviour</h1>

    <form action="{{ route('academics.student-behaviours.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Student</label>
            <div class="input-group">
                <input type="hidden" id="selectedStudentId" name="student_id" value="">
                <input type="text" id="selectedStudentName" class="form-control" placeholder="Search by name or admission number" readonly>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                    Search
                </button>
            </div>
        </div>

        {{-- include the search modal --}}
        @include('partials.student_search_modal')

        <div class="mb-3">
            <label>Behaviour</label>
            <select name="behaviour_id" class="form-select" required>
                @foreach($behaviours as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Term</label>
            <select name="term_id" class="form-select" required>
                @foreach($terms as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
                @foreach($years as $y)
                    <option value="{{ $y->id }}">{{ $y->year }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
