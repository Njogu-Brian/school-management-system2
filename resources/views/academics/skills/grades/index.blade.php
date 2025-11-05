@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Report Card Skills — Grading</h1>

    {{-- Selector --}}
    <form class="card card-body mb-4" method="get" action="{{ route('academics.skills.grade.index') }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach(\App\Models\AcademicYear::orderByDesc('year')->get() as $y)
                        <option value="{{ $y->id }}" @selected(($yearId ?? null)==$y->id)>{{ $y->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach(\App\Models\Term::orderBy('name')->get() as $t)
                        <option value="{{ $t->id }}" @selected(($termId ?? null)==$t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach(\App\Models\Academics\Classroom::orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" @selected(($classId ?? null)==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary">Load</button>
        </div>
    </form>

    @if(($yearId ?? null) && ($termId ?? null) && ($classId ?? null))
        <form method="post" action="{{ route('academics.skills.grade.store') }}" class="card">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $yearId }}">
            <input type="hidden" name="term_id" value="{{ $termId }}">

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:220px">Student</th>
                                @foreach($skills as $sk)
                                    <th style="min-width:140px">
                                        {{ $sk->name }}
                                        <div class="small text-muted">{{ $sk->description }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $i => $st)
                                <tr>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $st->id }}">
                                        <div class="fw-semibold">{{ $st->full_name }}</div>
                                        <div class="text-muted small">
                                            Adm: {{ $st->admission_number ?? '-' }} |
                                            Class: {{ $st->classroom->name ?? '-' }} 
                                            @if($st->stream) — {{ $st->stream->name }} @endif
                                        </div>
                                    </td>
                                    @foreach($skills as $sk)
                                        @php
                                            $existing = ($grades[$st->id][$sk->id] ?? collect())->first();
                                        @endphp
                                        <td>
                                            <select class="form-select form-select-sm mb-1"
                                                name="rows[{{ $i }}][skills][{{ $sk->id }}][grade]">
                                                <option value="">—</option>
                                                @foreach(['EE','ME','AE','BE'] as $opt)
                                                    <option value="{{ $opt }}" @selected(($existing?->grade)===$opt)>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="rows[{{ $i }}][skills][{{ $sk->id }}][report_card_skill_id]" value="{{ $sk->id }}">
                                            <input type="text" class="form-control form-control-sm"
                                                name="rows[{{ $i }}][skills][{{ $sk->id }}][comment]"
                                                value="{{ $existing?->comment }}" placeholder="Comment (optional)">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button class="btn btn-success">Save Grades</button>
                <a class="btn btn-outline-secondary" href="{{ url()->current() }}?academic_year_id={{ $yearId }}&term_id={{ $termId }}&classroom_id={{ $classId }}">Refresh</a>
            </div>
        </form>
    @endif
</div>
@endsection
