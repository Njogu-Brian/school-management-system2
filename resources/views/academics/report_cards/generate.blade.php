@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Generate Report Cards</h1>

    <div class="alert alert-info">
        This creates/updates report cards for the selected class & term by averaging all exams in the term.
    </div>

    <form method="post" action="{{ route('academics.report_cards.generate') }}" class="card card-body">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}">{{ $y->year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select" required>
                    <option value="">-- choose --</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Stream (optional)</label>
                <select name="stream_id" class="form-select">
                    <option value="">All streams</option>
                    @foreach($streams as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">
                <i class="bi bi-gear"></i> Generate Now
            </button>
            <a href="{{ route('academics.assessments.term') }}" class="btn btn-outline-secondary">
                View Term Assessment
            </a>
        </div>
    </form>
</div>
@endsection
