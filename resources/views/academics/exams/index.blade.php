@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      .exam-board {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
        gap: 1rem;
      }
      .exam-group {
        display: flex;
        flex-direction: column;
        min-width: 0;
      }
      .exam-group .card-body { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
      .paper-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
      .paper-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        max-width: 100%;
        padding: .28rem .65rem;
        border-radius: 999px;
        background: #f4f7fb;
        border: 1px solid #e4ebf3;
        color: inherit;
        text-decoration: none;
        font-size: .82rem;
        line-height: 1.2;
      }
      .paper-chip:hover { background: #e8f0f8; color: inherit; }
      .paper-chip strong { font-weight: 600; }
      .paper-chip span { color: #6c757d; white-space: nowrap; }
      .status-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
      .status-actions form { margin: 0; }
      .lifecycle-note { font-size: .85rem; }
    </style>
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Exams</h1>
        <p class="text-muted mb-0">Grouped by exam and class. Move a whole class together instead of one subject at a time.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.exams.bulk-create') }}" class="btn btn-ghost-strong"><i class="bi bi-layers"></i> Bulk create</a>
        <a href="{{ route('academics.exams.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> New Exam</a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <p class="lifecycle-note text-muted mb-3">
      New exams start as <strong>draft</strong> (no marks, hidden from parents).
      <strong>Marking</strong> lets teachers enter results.
      <strong>Published</strong> closes teacher entry; senior teachers and admins can still correct marks.
      <strong>Locked</strong> is what report cards and the parent portal use.
    </p>

    <div class="row g-3 mb-3">
      <div class="col-6 col-lg"><div class="settings-card stat-card border-start border-4 border-primary"><div class="card-body"><div class="text-muted text-uppercase small">Papers</div><h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3></div></div></div>
      <div class="col-6 col-lg"><div class="settings-card stat-card border-start border-4 border-secondary"><div class="card-body"><div class="text-muted text-uppercase small">Draft</div><h3 class="mb-0">{{ $stats['draft'] ?? 0 }}</h3></div></div></div>
      <div class="col-6 col-lg"><div class="settings-card stat-card border-start border-4 border-warning"><div class="card-body"><div class="text-muted text-uppercase small">Marking</div><h3 class="mb-0">{{ $stats['marking'] ?? 0 }}</h3></div></div></div>
      <div class="col-6 col-lg"><div class="settings-card stat-card border-start border-4 border-success"><div class="card-body"><div class="text-muted text-uppercase small">Published</div><h3 class="mb-0">{{ $stats['published'] ?? 0 }}</h3></div></div></div>
      <div class="col-6 col-lg"><div class="settings-card stat-card border-start border-4 border-danger"><div class="card-body"><div class="text-muted text-uppercase small">Locked</div><h3 class="mb-0">{{ $stats['locked'] ?? 0 }}</h3></div></div></div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Search</label>
              <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Class, subject, or exam">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label">Groups</label>
              <select name="per_page" class="form-select">
                @foreach([12, 24, 48] as $size)
                  <option value="{{ $size }}" @selected((int) request('per_page', $perPage ?? 12) === $size)>{{ $size }}</option>
                @endforeach
              </select>
            </div>
            @include('partials.academic_year_term_selects', [
                'yearName' => 'year_id',
                'termName' => 'term_id',
                'yearSelectId' => 'exam_index_year_id',
                'termSelectId' => 'exam_index_term_id',
                'selectedYearId' => $selectedYearId ?? null,
                'selectedTermId' => $selectedTermId ?? null,
                'allowEmptyYear' => true,
                'allowEmptyTerm' => true,
                'yearCol' => 'col-md-2',
                'termCol' => 'col-md-2',
            ])
            <div class="col-md-3">
              <label class="form-label">Exam</label>
              <select name="exam_type_id" class="form-select">
                <option value="">All exams</option>
                @foreach($types ?? [] as $t)
                  <option value="{{ $t->id }}" @selected(request('exam_type_id')==$t->id)>{{ $t->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row g-3 align-items-end mt-1">
            <div class="col-md-3">
              <label class="form-label">Class</label>
              <select name="classroom_id" class="form-select">
                <option value="">All classes</option>
                @foreach($classrooms ?? [] as $c)
                  <option value="{{ $c->id }}" @selected(request('classroom_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Subject</label>
              <select name="subject_id" class="form-select">
                <option value="">All subjects</option>
                @foreach($subjects ?? [] as $s)
                  <option value="{{ $s->id }}" @selected(request('subject_id')==$s->id)>{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(['draft' => 'Draft', 'marking' => 'Marking', 'published' => 'Published', 'locked' => 'Locked'] as $value => $label)
                  <option value="{{ $value }}" @selected(request('status')==$value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <h2 class="h5 mb-0">{{ $exams->total() }} class {{ \Illuminate\Support\Str::plural('group', $exams->total()) }}</h2>
    </div>

    @if($exams->isEmpty())
      <div class="settings-card"><div class="card-body text-muted">No exams match these filters.</div></div>
    @else
      <div class="exam-board">
        @foreach($exams as $group)
          @php
            $badge = match ($group['uniform']) {
                'draft' => 'secondary',
                'marking' => 'warning',
                'published' => 'success',
                'locked' => 'danger',
                default => 'info',
            };
            $statusLabel = $group['uniform'] ? ucfirst($group['uniform']) : 'Mixed';
          @endphp
          <article class="settings-card exam-group">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                  <h3 class="h6 mb-1">{{ $group['title'] }}</h3>
                  <div class="small text-muted">
                    {{ $group['term'] ?? 'Term' }}@if($group['year']) · {{ $group['year'] }}@endif
                    @if($group['stream']) · {{ $group['stream'] }}@endif
                  </div>
                </div>
                <span class="pill-badge pill-{{ $badge }}">{{ $statusLabel }}</span>
              </div>

              @if(! $group['uniform'])
                <div class="small text-muted">
                  {{ $group['counts']['draft'] }} draft ·
                  {{ $group['counts']['marking'] }} marking ·
                  {{ $group['counts']['published'] }} published ·
                  {{ $group['counts']['locked'] }} locked
                </div>
              @endif

              <div class="paper-grid">
                @foreach($group['papers'] as $exam)
                  <a class="paper-chip" href="{{ route('academics.exams.show', $exam->id) }}" title="{{ $exam->name }}">
                    <strong>{{ $exam->subject->name ?? $exam->name }}</strong>
                    <span>{{ $exam->marks_count }}</span>
                  </a>
                @endforeach
              </div>

              <div class="small text-muted">{{ $group['papers']->count() }} papers · {{ $group['marks'] }} marks entered</div>

              @if($canManageStatus)
                <div class="status-actions">
                  @foreach([
                    'marking' => 'Open for marking',
                    'published' => 'Publish',
                    'locked' => 'Lock',
                    'draft' => 'Back to draft',
                  ] as $status => $label)
                    @php
                      $already = $group['papers']->every(fn ($exam) => $exam->status === $status);
                    @endphp
                    <form method="POST" action="{{ route('academics.exams.bulk-update') }}">
                      @csrf
                      @foreach($group['ids'] as $id)
                        <input type="hidden" name="exam_ids[]" value="{{ $id }}">
                      @endforeach
                      <input type="hidden" name="bulk_status" value="{{ $status }}">
                      <button type="submit" class="btn btn-sm {{ $status === 'locked' ? 'btn-settings-primary' : 'btn-ghost-strong' }}" @disabled($already)
                        @if($status === 'locked') onclick="return confirm('Lock every paper in this class? Parents and report cards will include them.');" @endif
                        @if($status === 'draft') onclick="return confirm('Return every paper in this class to draft? Teachers will not be able to enter marks.');" @endif>
                        {{ $label }}
                      </button>
                    </form>
                  @endforeach
                </div>
              @endif
            </div>
          </article>
        @endforeach
      </div>

      <div class="mt-3">{{ $exams->links() }}</div>
    @endif
  </div>
</div>
@endsection
