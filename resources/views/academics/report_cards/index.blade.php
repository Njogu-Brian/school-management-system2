@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Report Cards</h1>
    <a href="{{ route('report-cards.create') }}" class="btn btn-primary mb-3">Generate Report Card</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Term</th>
                <th>Year</th>
                <th>Status</th>
                <th>Published</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportCards as $reportCard)
            <tr>
                <td>{{ $reportCard->student->full_name }}</td>
                <td>{{ $reportCard->classroom->name }}</td>
                <td>{{ $reportCard->term->name }}</td>
                <td>{{ $reportCard->academicYear->year }}</td>
                <td>{{ ucfirst($reportCard->status) }}</td>
                <td>{{ $reportCard->published_at ? $reportCard->published_at->format('d M Y') : '-' }}</td>
                <td>
                    <a href="{{ route('report-cards.show',$reportCard) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('report-cards.edit',$reportCard) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('report-cards.destroy',$reportCard) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this report card?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $reportCards->links() }}
</div>
@endsection
