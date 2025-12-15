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

    @if(!empty($data))
        @php
            $exams = $data['exams'];
            // group rows by student
            $byStudent = collect($data['rows'])->groupBy(fn($r) => $r['student']->id);
        @endphp

        @foreach($byStudent as $studentId => $rows)
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <strong>{{ $rows->first()['student']->full_name }}</strong>
                    <span class="text-muted ms-2">
                        Adm: {{ $rows->first()['student']->admission_number ?? '-' }} |
                        Class: {{ $rows->first()['student']->classroom->name ?? '-' }}
                        @if($rows->first()['student']->stream) — Stream: {{ $rows->first()['student']->stream->name }} @endif
                    </span>
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

        @if($byStudent->isEmpty())
            <div class="alert alert-info">No marks found for the selected filters.</div>
        @endif
    @endif
</div>
@endsection
