@php
  use App\Models\Academics\ExamGroup;
  use App\Models\Academics\ExamType;
  use App\Models\AcademicYear;
  use App\Models\Term;

  $groups = $groups ?? ExamGroup::orderBy('name')->get();
  $types  = $types  ?? ExamType::orderBy('name')->get();
  $years  = $years  ?? AcademicYear::orderByDesc('year')->get();
  $terms  = $terms  ?? Term::orderBy('name')->get();

  // defaults
  $v = [
    'name' => old('name', $exam->name ?? ''),
    'exam_group_id' => old('exam_group_id', $exam->exam_group_id ?? request('group_id')),
    'type' => old('type', $exam->type ?? 'cat'),
    'modality' => old('modality', $exam->modality ?? 'physical'),
    'academic_year_id' => old('academic_year_id', $exam->academic_year_id ?? ($years->first()->id ?? null)),
    'term_id' => old('term_id', $exam->term_id ?? ($terms->first()->id ?? null)),
    'starts_on' => old('starts_on', optional($exam->starts_on ?? null)?->format('Y-m-d')),
    'ends_on'   => old('ends_on',   optional($exam->ends_on ?? null)?->format('Y-m-d')),
    'max_marks' => old('max_marks', $exam->max_marks ?? 100),
    'weight'    => old('weight', $exam->weight ?? 1.00),
    'publish_exam'   => old('publish_exam', $exam->publish_exam ?? false),
    'publish_result' => old('publish_result', $exam->publish_result ?? true),
    'status'    => old('status', $exam->status ?? 'draft'),
  ];
@endphp

<div class="row">
  <div class="col-lg-8">
    <div class="row">
      <div class="col-md-8 mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" required value="{{ $v['name'] }}" placeholder="e.g. Monthly Exam (Nov-2025)">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Exam Group <span class="text-danger">*</span></label>
        <select name="exam_group_id" class="form-select" required>
          <option value="">Select</option>
          @foreach($groups as $g)
            <option value="{{ $g->id }}" @selected($v['exam_group_id']==$g->id)>{{ $g->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Exam Type (enum)</label>
        {{-- keeps compatibility with your existing enum: cat, midterm, endterm, sba, mock, quiz --}}
        <select name="type" class="form-select" required>
          @foreach(['cat','midterm','endterm','sba','mock','quiz'] as $t)
            <option value="{{ $t }}" @selected($v['type']==$t)>{{ strtoupper($t) }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Modality</label>
        <select name="modality" class="form-select" required>
          @foreach(['physical','online'] as $m)
            <option value="{{ $m }}" @selected($v['modality']==$m)>{{ ucfirst($m) }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2 mb-3">
        <label class="form-label">Max Marks</label>
        <input type="number" step="1" min="1" name="max_marks" class="form-control" value="{{ $v['max_marks'] }}">
      </div>

      <div class="col-md-2 mb-3">
        <label class="form-label">Weight</label>
        <input type="number" step="0.01" min="0" name="weight" class="form-control" value="{{ $v['weight'] }}">
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Academic Year</label>
        <select name="academic_year_id" class="form-select" required>
          @foreach($years as $y)
            <option value="{{ $y->id }}" @selected($v['academic_year_id']==$y->id)>{{ $y->year }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Term</label>
        <select name="term_id" class="form-select" required>
          @foreach($terms as $t)
            <option value="{{ $t->id }}" @selected($v['term_id']==$t->id)>{{ $t->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Starts On</label>
        <input type="date" name="starts_on" class="form-control" value="{{ $v['starts_on'] }}">
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Ends On</label>
        <input type="date" name="ends_on" class="form-control" value="{{ $v['ends_on'] }}">
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="border rounded p-3 bg-light h-100">
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" role="switch" id="publishExam" name="publish_exam" value="1" @checked($v['publish_exam'])>
        <label class="form-check-label" for="publishExam">Publish Exam (visible on schedule)</label>
      </div>

      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" role="switch" id="publishResult" name="publish_result" value="1" @checked($v['publish_result'])>
        <label class="form-check-label" for="publishResult">Publish Result (eligible for report cards)</label>
      </div>

      @if(($mode ?? 'create') === 'edit')
        <div class="mb-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            @foreach(['draft','open','marking','moderation','approved','published','locked'] as $st)
              <option value="{{ $st }}" @selected($v['status']==$st)>{{ ucfirst($st) }}</option>
            @endforeach
          </select>
        </div>
      @else
        <input type="hidden" name="status" value="draft">
      @endif

      <div class="small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Results will only be pushed to report cards when the exam is
        <strong>Approved/Locked</strong> and <strong>Publish Result</strong> is on.
      </div>
    </div>
  </div>
</div>
