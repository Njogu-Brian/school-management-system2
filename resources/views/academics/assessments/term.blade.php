@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Term Assessment</h1>

    {{-- Filters --}}
    <form class="card card-body mb-4" method="get" action="{{ route('academics.assessments.term') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected(($selected['yearId'] ?? $currentYearId ?? null)==$y->id)>{{ $y->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" @selected(($selected['termId'] ?? $currentTermId ?? null)==$t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected(($selected['classId'] ?? null)==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subject (optional)</label>
                <select name="subject_id" class="form-select">
                    <option value="">All subjects</option>
                    @if(!empty($data))
                        @php
                            $subjectIds = collect($data['rows'] ?? [])->pluck('subject_id')->unique();
                            $subjectNames = collect($data['rows'] ?? [])->mapWithKeys(function($r){
                                return [$r['subject_id'] => $r['marks']->first()?->subject?->name];
                            });
                        @endphp
                        @foreach($subjectIds as $sid)
                            <option value="{{ $sid }}" @selected(($selected['subjectId'] ?? null)==$sid)>{{ $subjectNames[$sid] ?? 'Subject' }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Apply</button>
        </div>
    </form>

    @if(empty($data) || empty($data['rows']))
        <div class="alert alert-info">No marks found for the selected filters.</div>
    @else
        @php
            $exams = $data['exams'];
            $byStudent = collect($data['rows'])->groupBy(fn($r) => $r['student']->id);
        @endphp

        @foreach($byStudent as $studentId => $rows)
            @php
                $student = $rows->first()['student'];
                $subjectCount = $rows->count();
                $avgTerm = $rows->avg('avg');
                $best = $rows->sortByDesc('avg')->first();
                $worst = $rows->sortBy('avg')->first();
            @endphp
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>{{ $student->full_name }}</strong>
                        <span class="text-muted ms-2">
                            Adm: {{ $student->admission_number ?? '-' }} |
                            Class: {{ $student->classroom->name ?? '-' }}
                            @if($student->stream) — Stream: {{ $student->stream->name }} @endif
                        </span>
                    </div>
                    <div class="d-flex gap-3 small text-muted">
                        <span>Subjects: <strong>{{ $subjectCount }}</strong></span>
                        <span>Avg: <strong>{{ $avgTerm ? number_format($avgTerm,2) : '—' }}</strong></span>
                        <span>Best: <strong>{{ $best?->marks->first()?->subject?->name ?? '-' }}</strong> ({{ $best?->avg ? number_format($best->avg,2) : '—' }})</span>
                        <span>Lowest: <strong>{{ $worst?->marks->first()?->subject?->name ?? '-' }}</strong> ({{ $worst?->avg ? number_format($worst->avg,2) : '—' }})</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:180px">Subject</th>
                                    @foreach($exams as $ex)
                                        <th>{{ $ex->name }}</th>
                                    @endforeach
                                    <th>Term Avg</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $r)
                                    @php
                                        $byExam = $r['marks']->keyBy('exam_id');
                                    @endphp
                                    <tr>
                                        <td>{{ $r['marks']->first()?->subject?->name ?? 'Subject' }}</td>
                                        @foreach($exams as $ex)
                                            <td>{{ optional($byExam->get($ex->id))->score_raw !== null ? number_format($byExam->get($ex->id)->score_raw, 2) : '—' }}</td>
                                        @endforeach
                                        <td><strong>{{ $r['avg'] !== null ? number_format($r['avg'],2) : '—' }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>                    
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
