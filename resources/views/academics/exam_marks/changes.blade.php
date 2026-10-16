@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
@php
    $formatMark = function ($value) {
        $n = round((float) $value, 2);
        if (abs($n - round($n)) < 0.001) {
            return (string) (int) round($n);
        }

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };
@endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Academics · Exams</div>
                <h1 class="mb-1">Exam mark changes</h1>
                <p class="text-muted mb-0">Marks edited after they were already entered. Each paper shows the score before and after.</p>
            </div>
            @if($batch && $items->isNotEmpty())
                <form method="post" action="{{ route('academics.exam-marks.changes.review') }}" onsubmit="return confirm('Mark these exam changes as reviewed?');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2"></i> Mark as reviewed
                    </button>
                </form>
            @endif
        </div>

        @includeIf('partials.alerts')

        @if(!$batch || $items->isEmpty())
            <div class="settings-card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-check2-circle d-block mb-2" style="font-size:1.6rem;"></i>
                    No exam mark changes are waiting for review.
                </div>
            </div>
        @else
            <div class="settings-card mb-3">
                <div class="card-body d-flex flex-wrap gap-4">
                    <div>
                        <div class="text-muted small">Staff</div>
                        <div class="fs-4 fw-semibold">{{ $summary['staff_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Papers</div>
                        <div class="fs-4 fw-semibold">{{ $summary['paper_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Marks changed</div>
                        <div class="fs-4 fw-semibold">{{ $summary['change_count'] }}</div>
                    </div>
                    <div class="ms-md-auto align-self-center text-muted">
                        {{ $summary['body'] }}
                    </div>
                </div>
            </div>

            <form method="get" class="settings-card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label" for="mark-change-paper">Paper</label>
                            <select id="mark-change-paper" name="exam" class="form-select">
                                <option value="">All papers</option>
                                @foreach($paperOptions as $paper)
                                    <option value="{{ $paper->exam_id }}" @selected($examId === (int) $paper->exam_id)>
                                        {{ $paper->exam_name }}@if($paper->paperMeta()) — {{ $paper->paperMeta() }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="mark-change-staff">Staff</label>
                            <select id="mark-change-staff" name="staff" class="form-select">
                                <option value="">All staff</option>
                                @foreach($staffOptions as $name)
                                    <option value="{{ $name }}" @selected($staff === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-settings-primary flex-grow-1" type="submit">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            @if($filtered)
                                <a class="btn btn-ghost-strong" href="{{ route('academics.exam-marks.changes') }}">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @forelse($groups as $examIdGroup => $changes)
                @php $paper = $changes->first(); @endphp
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $paper->exam_name ?: 'Exam' }}</div>
                            @if($paper->paperMeta())
                                <div class="text-muted small">{{ $paper->paperMeta() }}</div>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pill-badge pill-muted">{{ $changes->count() }} {{ \Illuminate\Support\Str::plural('change', $changes->count()) }}</span>
                            @if(Route::has('academics.exams.show'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('academics.exams.show', $paper->exam_id) }}">Open exam</a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Changed by</th>
                                        <th class="text-end">Before</th>
                                        <th class="text-end">After</th>
                                        <th class="text-end">Change</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($changes as $change)
                                        @php
                                            $delta = (float) $change->score_to - (float) $change->score_from;
                                            $deltaLabel = ($delta > 0 ? '+' : '').$formatMark($delta);
                                        @endphp
                                        <tr>
                                            <td>{{ $change->student_name }}</td>
                                            <td>{{ $change->editor_name }}</td>
                                            <td class="text-end">{{ $formatMark($change->score_from) }}</td>
                                            <td class="text-end fw-semibold">{{ $formatMark($change->score_to) }}</td>
                                            <td class="text-end">{{ $deltaLabel }}</td>
                                            <td>{{ $change->changed_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="settings-card">
                    <div class="card-body text-center text-muted py-4">No changes match this filter.</div>
                </div>
            @endforelse
        @endif
    </div>
</div>
@endsection
