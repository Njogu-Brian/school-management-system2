@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Behaviour Records</h1>

    <a href="{{ route('academics.student-behaviours.create') }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus"></i> Record Behaviour
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Student</th>
                <th>Behaviour</th>
                <th>Term</th>
                <th>Year</th>
                <th>Recorded By</th>
                <th>Notes</th>
                <th width="100">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $rec)
                <tr>
                    <td>{{ $rec->student->full_name }}</td>
                    <td>{{ $rec->behaviour->name }}</td>
                    <td>{{ $rec->term->name }}</td>
                    <td>{{ $rec->academicYear->year }}</td>
                    <td>{{ $rec->teacher->full_name ?? 'N/A' }}</td>
                    <td>{{ $rec->notes }}</td>
                    <td>
                        <form action="{{ route('academics.student-behaviours.destroy',$rec) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No behaviour records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
