@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">{{ strtoupper($campus) }} CAMPUS HEATMAP</h3>
            <div class="text-muted">Class averages by subject</div>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="week_ending" value="{{ $weekEnding }}" class="form-control" />
            <button class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            @foreach ($subjects as $subject)
                                <th class="text-nowrap">{{ $subject->name }}</th>
                            @endforeach
                            <th class="text-nowrap">Class Avg %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classrooms as $classroom)
                            @php
                                $rows = $averages->get($classroom->id, collect());
                                $subjectAverages = $rows->keyBy('subject_id');
                                $classAvg = $rows->avg('avg_percent');
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $classroom->name }}</td>
                                @foreach ($subjects as $subject)
                                    @php
                                        $avg = optional($subjectAverages->get($subject->id))->avg_percent;
                                        $color = $avg === null ? '#f8f9fa' : ($avg >= 80 ? '#d4edda' : ($avg >= 60 ? '#fff3cd' : '#f8d7da'));
                                    @endphp
                                    <td style="background-color: {{ $color }}; text-align:center;">
                                        {{ $avg !== null ? number_format($avg, 1) : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-center fw-bold">
                                    {{ $classAvg !== null ? number_format($classAvg, 1) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $subjects->count() + 2 }}" class="text-center text-muted p-4">
                                    No classes found for this campus.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
