@extends('layouts.app')

@section('content')
@php
  use App\Models\Academics\ExamGroup;
  use App\Models\Academics\ExamType;
  use App\Models\AcademicYear;
  use App\Models\Term;

  $groups = $groups ?? ExamGroup::withCount('exams')->orderBy('name')->get();
  $types  = $types  ?? ExamType::orderBy('name')->get();
  $years  = $years  ?? AcademicYear::orderByDesc('year')->get();
  $terms  = $terms  ?? Term::orderBy('name')->get();
@endphp

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Exams</h3>
      <small class="text-muted">Create, manage, schedule, enter marks, and publish.</small>
    </div>
    <a href="{{ route('academics.exams.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> New Exam
    </a>
  </div>

  @includeIf('partials.alerts')

  <!-- Filters -->
  <form method="get" class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Exam Group</label>
          <select name="group_id" class="form-select" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($groups as $g)
              <option value="{{ $g->id }}" @selected(request('group_id')==$g->id)>{{ $g->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Type</label>
          <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($types as $t)
              <option value="{{ $t->code }}" @selected(request('type')==$t->code)>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Year</label>
          <select name="year_id" class="form-select" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($years as $y)
              <option value="{{ $y->id }}" @selected(request('year_id')==$y->id)>{{ $y->year }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Term</label>
          <select name="term_id" class="form-select" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($terms as $t)
              <option value="{{ $t->id }}" @selected(request('term_id')==$t->id)>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Any</option>
            @foreach(['draft','open','marking','moderation','approved','published','locked'] as $st)
              <option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button>
        </div>
      </div>
    </div>
  </form>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Group</th>
              <th>Type</th>
              <th>AY / Term</th>
              <th class="text-center">Publish Exam</th>
              <th class="text-center">Publish Result</th>
              <th class="text-center">Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($exams ?? [] as $exam)
              <tr>
                <td class="fw-semibold">{{ $exam->name }}</td>
                <td>{{ $exam->group?->name ?? '—' }}</td>
                <td>{{ ucfirst($exam->type) }}</td>
                <td>{{ $exam->academicYear?->year ?? '-' }} / {{ $exam->term?->name ?? '-' }}</td>
                <td class="text-center">
                  @if($exam->publish_exam)
                    <span class="badge bg-success">Yes</span>
                  @else
                    <span class="badge bg-secondary">No</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($exam->publish_result)
                    <span class="badge bg-success">Yes</span>
                  @else
                    <span class="badge bg-secondary">No</span>
                  @endif
                </td>
                <td class="text-center"><span class="badge text-bg-light">{{ ucfirst($exam->status) }}</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" title="Schedule"
                     href="{{ route('exams.schedules.index', $exam->id) }}">
                    <i class="bi bi-calendar-week"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-secondary" title="Enter Results"
                     href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-secondary" title="Timetable"
                     href="{{ route('academics.exams.timetable') }}">
                    <i class="bi bi-printer"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-primary" title="Edit"
                     href="{{ route('academics.exams.edit', $exam->id) }}">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form action="{{ route('academics.exams.destroy', $exam->id) }}" method="post" class="d-inline"
                        onsubmit="return confirm('Delete this exam?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                  @if($exam->publish_result && in_array($exam->status,['approved','published','locked']))
                    <form action="{{ route('exams.publish', $exam->id) }}" method="post" class="d-inline"
                          onsubmit="return confirm('Publish results to report cards?');">
                      @csrf
                      <button class="btn btn-sm btn-success" title="Publish"><i class="bi bi-cloud-upload"></i></button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">No exams yet. Create one.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if(isset($exams) && method_exists($exams,'links'))
      <div class="card-footer">{{ $exams->withQueryString()->links() }}</div>
    @endif
  </div>
</div>
@endsection
