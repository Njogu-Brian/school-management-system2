@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 fw-bold">Exam Timetable</h1>

    @forelse($schedules as $day => $list)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">
                {{ \Carbon\Carbon::parse($day)->format('l, d M Y') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 15%;">Time</th>
                            <th style="width: 25%;">Exam</th>
                            <th style="width: 20%;">Subject</th>
                            <th style="width: 20%;">Classroom</th>
                            <th style="width: 20%;">Term / Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list as $schedule)
                            <tr>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                        –
                                        {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $schedule->exam->name }}</strong>
                                    <span class="badge bg-secondary ms-1">{{ strtoupper($schedule->exam->type) }}</span>
                                </td>
                                <td>{{ $schedule->subject->name ?? '-' }}</td>
                                <td><i class="bi bi-house"></i> {{ $schedule->classroom->name ?? '-' }}</td>
                                <td>{{ $schedule->exam->term->name ?? '-' }} / {{ $schedule->exam->academicYear->year ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No scheduled exam papers.</div>
    @endforelse
</div>
@endsection
