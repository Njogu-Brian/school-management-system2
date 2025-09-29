@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Exams</h1>
    <a href="{{ route('academics.exams.create') }}" class="btn btn-primary mb-3">Add Exam</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Subjects</th>
                <th>Classrooms</th>
                <th>Term / Year</th>
                <th>Schedule</th>
                <th>Max / Weight</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exams as $exam)
            <tr>
                <td>{{ $exam->name }}</td>
                <td>{{ strtoupper($exam->type) }}</td>

                {{-- ✅ Subjects --}}
                <td>
                    @if($exam->subjects->count())
                        {{ $exam->subjects->pluck('name')->unique()->join(', ') }}
                    @else
                        N/A
                    @endif
                </td>

                {{-- ✅ Classrooms --}}
                <td>
                    @if($exam->classrooms->count())
                        {{ $exam->classrooms->pluck('name')->unique()->join(', ') }}
                    @else
                        N/A
                    @endif
                </td>

                {{-- Term / Year --}}
                <td>{{ optional($exam->term)->name }} / {{ optional($exam->academicYear)->year }}</td>

                {{-- Schedule --}}
                <td>
                    @if($exam->starts_on && $exam->ends_on)
                        {{ $exam->starts_on->format('d M Y, H:i') }} - {{ $exam->ends_on->format('H:i') }}
                    @else
                        Not Scheduled
                    @endif
                </td>

                {{-- Max / Weight --}}
                <td>{{ number_format($exam->max_marks, 2) }} / {{ number_format($exam->weight, 2) }}%</td>

                {{-- Actions --}}
                <td>
                    <a href="{{ route('academics.exams.edit',$exam) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('academics.exams.destroy',$exam) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete exam?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $exams->links() }}
</div>
@endsection
