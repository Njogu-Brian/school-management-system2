@php
  use App\Support\CbcGradePresentation;

  $subjects = $payload['subjects'] ?? [];
  $rows = $payload['rows'] ?? [];
  $meta = $payload['meta'] ?? [];
  $classroomId = (int) ($meta['classroom']['id'] ?? 0);
  $streamFiltered = ! empty($meta['stream_id']);
  $streamNamesById = $meta['stream_names_by_id'] ?? [];
  $sittings = $payload['sittings'] ?? ($meta['sittings'] ?? []);
  $isTermSittings = ($meta['mode'] ?? '') === 'term' && ($meta['layout'] ?? '') === 'term_sittings' && ! empty($sittings);
  $parallel = $meta['parallel'] ?? [];
  $sittingClassAverages = $meta['sitting_class_averages'] ?? [];

  $resolveStreamName = function (array $r) use ($streamNamesById): ?string {
    if (! empty($r['stream_name'])) {
      return $r['stream_name'];
    }
    $sid = $r['stream_id'] ?? null;
    return ($sid && isset($streamNamesById[$sid])) ? $streamNamesById[$sid] : null;
  };

  $showStreamColumn = ($showStreamColumn ?? false) || $streamFiltered;

  $rowCount = count($rows);
  $densityClass = 'class-sheet-density--'.($rowCount <= 18 ? 'normal' : ($rowCount <= 32 ? 'compact' : 'tight'));
  $fitOnePageClass = $rowCount <= 40 ? 'class-sheet-fit-one-page' : '';

  $subjectAverages = collect($subjects)->mapWithKeys(function ($s) use ($rows) {
    $sid = $s['id'] ?? null;
    if (! $sid) return [];

    $vals = collect($rows)
      ->map(fn ($r) => data_get($r, "subject_scores.$sid"))
      ->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v))
      ->map(fn ($v) => (float) $v);

    $avg = $vals->count() ? round($vals->avg(), 2) : null;
    return [$sid => $avg];
  });

  $classMeanValues = collect($rows)
    ->pluck('average')
    ->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v))
    ->map(fn ($v) => (float) $v);
  $classMean = $classMeanValues->count() ? round($classMeanValues->avg(), 2) : null;
  $classMeanGrade = CbcGradePresentation::forPercentage($classMean, $classroomId ?: null);

  if ($isTermSittings) {
    $summaryColspan = 3
      + (count($sittings) * 2)
      + 2
      + 1
      + (! empty($parallel['classroom_count']) && (int) $parallel['classroom_count'] > 1 ? 1 : 0)
      + ($showStreamColumn ? 1 : 0);
  } else {
    $summaryColspan = 3 + count($subjects) + 2 + ($showStreamColumn ? 1 : 0);
  }
@endphp

@if($isTermSittings)
  <div class="px-3 pt-3 pb-1 small text-muted d-print-none">
    Whole-term overview of
    <strong>{{ collect($sittings)->pluck('label')->filter()->implode(', ') }}</strong>
    (KNEC CBA excluded).
    Term Avg uses subject averages across those exams.
    @if(! empty($parallel['classroom_count']) && (int) $parallel['classroom_count'] > 1)
      Grade rank compares
      <strong>{{ $parallel['grade_label'] ?? 'this grade' }}</strong>
      across {{ (int) $parallel['classroom_count'] }} classes
      ({{ collect($parallel['classroom_names'] ?? [])->implode(', ') }}).
    @endif
  </div>
@endif

<div class="table-responsive">
  <table class="table table-modern align-middle mb-0 table-sm class-sheet-data-table exam-report-marks-table {{ $densityClass }} {{ $fitOnePageClass }}">
    @if($isTermSittings)
      <thead class="table-light">
        <tr>
          <th class="text-center er-th" rowspan="2">#</th>
          <th class="text-center er-th" rowspan="2">
            <span class="er-hdr er-hdr--print">Adm<br>No</span>
            <span class="er-hdr er-hdr--screen">Adm No</span>
          </th>
          <th class="er-th" rowspan="2">
            <span class="er-hdr er-hdr--print">Stud<br>ent</span>
            <span class="er-hdr er-hdr--screen">Student</span>
          </th>
          @foreach($sittings as $sitting)
            <th class="text-center er-th" colspan="2">{{ $sitting['label'] }}</th>
          @endforeach
          <th class="text-center er-th" colspan="2">Term</th>
          <th class="text-center er-th" rowspan="2">Cls</th>
          @if(! empty($parallel['classroom_count']) && (int) $parallel['classroom_count'] > 1)
            <th class="text-center er-th" rowspan="2" title="Rank among {{ collect($parallel['classroom_names'] ?? [])->implode(', ') }}">
              Grade
            </th>
          @endif
          @if($showStreamColumn)
            <th class="text-center er-th" rowspan="2">{{ $streamFiltered ? 'Str' : 'Stream' }}</th>
          @endif
        </tr>
        <tr>
          @foreach($sittings as $sitting)
            <th class="text-center er-th">Tot</th>
            <th class="text-center er-th">Avg</th>
          @endforeach
          <th class="text-center er-th">Tot</th>
          <th class="text-center er-th">Avg</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $r)
          @php
            $streamLabel = $resolveStreamName($r);
            $avgGrade = CbcGradePresentation::forPercentage(
              is_numeric($r['average'] ?? null) ? (float) $r['average'] : null,
              $classroomId ?: null
            );
          @endphp
          <tr>
            <td class="text-center er-td">{{ $i + 1 }}</td>
            <td class="text-center er-td">{{ $r['admission_number'] ?? '' }}</td>
            <td class="er-td">
              <span class="er-student-name">{{ $r['name'] ?? '' }}</span>
              @if($showStreamColumn && ! $streamFiltered && $streamLabel)
                <span class="mark-sheet-stream-pill er-stream-pill--screen">{{ $streamLabel }}</span>
              @endif
            </td>
            @foreach($sittings as $sitting)
              @php
                $sit = data_get($r, 'sitting_scores.'.$sitting['key'], []);
                $sitAvg = $sit['average'] ?? null;
                $sitAvgGrade = CbcGradePresentation::forPercentage(
                  is_numeric($sitAvg) ? (float) $sitAvg : null,
                  $classroomId ?: null
                );
              @endphp
              <td class="text-center er-td mark-sheet-score">{{ $sit['total'] ?? '—' }}</td>
              <td class="text-center er-td er-score-cell">
                <span class="mark-sheet-score">{{ $sitAvg ?? '—' }}</span>@if($sitAvgGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $sitAvgGrade])@endif
              </td>
            @endforeach
            <td class="text-center er-td mark-sheet-score">{{ $r['total'] ?? '—' }}</td>
            <td class="text-center er-td er-score-cell">
              <span class="mark-sheet-score">{{ $r['average'] ?? '—' }}</span>@if($avgGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $avgGrade])@endif
            </td>
            <td class="text-center er-td fw-semibold">{{ $r['class_position'] ?? $r['position'] ?? '—' }}</td>
            @if(! empty($parallel['classroom_count']) && (int) $parallel['classroom_count'] > 1)
              <td class="text-center er-td fw-semibold">{{ $r['grade_position'] ?? '—' }}</td>
            @endif
            @if($showStreamColumn)
              <td class="text-center er-td er-stream-cell">
                @if($streamFiltered)
                  {{ $r['stream_position'] ?? '—' }}
                @else
                  {{ $streamLabel ?? '—' }}
                @endif
              </td>
            @endif
          </tr>
        @empty
          <tr><td colspan="{{ $summaryColspan }}" class="text-center text-muted py-4">No rows.</td></tr>
        @endforelse
      </tbody>
      @if(! empty($rows))
        <tfoot>
          <tr class="class-mean-row">
            <td colspan="3" class="er-td fw-bold">Class average</td>
            @foreach($sittings as $sitting)
              @php
                $sitClassAvg = $sittingClassAverages[$sitting['key']] ?? null;
                $sitClassAvgGrade = CbcGradePresentation::forPercentage(
                  is_numeric($sitClassAvg) ? (float) $sitClassAvg : null,
                  $classroomId ?: null
                );
              @endphp
              <td class="text-center er-td"></td>
              <td class="text-center er-td er-score-cell">
                <span class="fw-bold">{{ $sitClassAvg ?? '—' }}</span>@if($sitClassAvgGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $sitClassAvgGrade])@endif
              </td>
            @endforeach
            <td class="text-center er-td"></td>
            <td class="text-center er-td er-score-cell">
              <span class="fw-bold">{{ $classMean ?? '—' }}</span>@if($classMeanGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $classMeanGrade])@endif
            </td>
            <td class="text-center er-td"></td>
            @if(! empty($parallel['classroom_count']) && (int) $parallel['classroom_count'] > 1)
              <td class="text-center er-td"></td>
            @endif
            @if($showStreamColumn)
              <td class="text-center er-td"></td>
            @endif
          </tr>
        </tfoot>
      @endif
    @else
      <colgroup>
        <col class="er-col er-col--idx">
        <col class="er-col er-col--adm">
        <col class="er-col er-col--student">
        @foreach($subjects as $s)
          <col class="er-col er-col--score">
        @endforeach
        <col class="er-col er-col--total">
        <col class="er-col er-col--avg">
        <col class="er-col er-col--cls">
        @if($showStreamColumn)
          <col class="er-col er-col--str">
        @endif
      </colgroup>
      <thead class="table-light">
        <tr>
          <th class="text-center er-th">#</th>
          <th class="text-center er-th">
            <span class="er-hdr er-hdr--print">Adm<br>No</span>
            <span class="er-hdr er-hdr--screen">Adm No</span>
          </th>
          <th class="er-th">
            <span class="er-hdr er-hdr--print">Stud<br>ent</span>
            <span class="er-hdr er-hdr--screen">Student</span>
          </th>
          @foreach($subjects as $s)
            <th class="text-center er-th">
              {{ $s['code'] ? $s['code'] : $s['name'] }}
            </th>
          @endforeach
          <th class="text-center er-th">Total</th>
          <th class="text-center er-th">Avg</th>
          <th class="text-center er-th">Cls</th>
          @if($showStreamColumn)
            <th class="text-center er-th">{{ $streamFiltered ? 'Str' : 'Stream' }}</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $r)
          @php
            $streamLabel = $resolveStreamName($r);
            $avgGrade = CbcGradePresentation::forPercentage(
              is_numeric($r['average'] ?? null) ? (float) $r['average'] : null,
              $classroomId ?: null
            );
          @endphp
          <tr>
            <td class="text-center er-td">{{ $i + 1 }}</td>
            <td class="text-center er-td">{{ $r['admission_number'] ?? '' }}</td>
            <td class="er-td">
              <span class="er-student-name">{{ $r['name'] ?? '' }}</span>
              @if($showStreamColumn && ! $streamFiltered && $streamLabel)
                <span class="mark-sheet-stream-pill er-stream-pill--screen">{{ $streamLabel }}</span>
              @endif
            </td>
            @foreach($subjects as $s)
              @php
                $sid = $s['id'];
                $score = data_get($r, "subject_scores.$sid");
                $scoreGrade = CbcGradePresentation::forPercentage(
                  is_numeric($score) ? (float) $score : null,
                  $classroomId ?: null
                );
              @endphp
              <td class="text-center er-td er-score-cell">
                <span class="mark-sheet-score">{{ $score !== null && $score !== '' ? $score : '—' }}</span>@if($scoreGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $scoreGrade])@endif
              </td>
            @endforeach
            <td class="text-center er-td mark-sheet-score">{{ $r['total'] ?? '—' }}</td>
            <td class="text-center er-td er-score-cell">
              <span class="mark-sheet-score">{{ $r['average'] ?? '—' }}</span>@if($avgGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $avgGrade])@endif
            </td>
            <td class="text-center er-td fw-semibold">{{ $r['class_position'] ?? $r['position'] ?? '—' }}</td>
            @if($showStreamColumn)
              <td class="text-center er-td er-stream-cell">
                @if($streamFiltered)
                  {{ $r['stream_position'] ?? '—' }}
                @else
                  {{ $streamLabel ?? '—' }}
                @endif
              </td>
            @endif
          </tr>
        @empty
          <tr><td colspan="{{ $summaryColspan }}" class="text-center text-muted py-4">No rows.</td></tr>
        @endforelse
      </tbody>
      @if(!empty($rows))
        <tfoot>
          <tr>
            <td colspan="3" class="er-td fw-semibold">Subject average</td>
            @foreach($subjects as $s)
              @php
                $sid = $s['id'];
                $subAvg = $subjectAverages[$sid] ?? null;
                $subAvgGrade = CbcGradePresentation::forPercentage(
                  is_numeric($subAvg) ? (float) $subAvg : null,
                  $classroomId ?: null
                );
              @endphp
              <td class="text-center er-td er-score-cell">
                <span class="fw-semibold">{{ isset($subAvg) ? $subAvg : '—' }}</span>@if($subAvgGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $subAvgGrade])@endif
              </td>
            @endforeach
            <td class="text-center er-td"></td>
            <td class="text-center er-td"></td>
            <td class="text-center er-td"></td>
            @if($showStreamColumn)
              <td class="text-center er-td"></td>
            @endif
          </tr>
          <tr class="class-mean-row">
            <td colspan="3" class="er-td fw-bold">Class mean score</td>
            @foreach($subjects as $s)
              <td class="text-center er-td"></td>
            @endforeach
            <td class="text-center er-td"></td>
            <td class="text-center er-td er-score-cell">
              <span class="fw-bold">{{ $classMean ?? '—' }}</span>@if($classMeanGrade)@include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $classMeanGrade])@endif
            </td>
            <td class="text-center er-td"></td>
            @if($showStreamColumn)
              <td class="text-center er-td"></td>
            @endif
          </tr>
        </tfoot>
      @endif
    @endif
  </table>
</div>

@if($isTermSittings && ! empty($subjects) && ! empty($rows))
  <div class="px-3 pt-3">
    <h6 class="mb-2 d-flex align-items-center gap-2">
      <i class="bi bi-journal-text"></i>
      Term subject averages
      <span class="text-muted small fw-normal">(average across exams in this term)</span>
    </h6>
  </div>
  <div class="table-responsive px-0 pb-2">
    <table class="table table-modern align-middle mb-0 table-sm class-sheet-data-table exam-report-marks-table {{ $densityClass }}">
      <thead class="table-light">
        <tr>
          <th class="text-center er-th">#</th>
          <th class="text-center er-th">Adm No</th>
          <th class="er-th">Student</th>
          @foreach($subjects as $s)
            <th class="text-center er-th">{{ $s['code'] ? $s['code'] : $s['name'] }}</th>
          @endforeach
          <th class="text-center er-th">Tot</th>
          <th class="text-center er-th">Avg</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $i => $r)
          <tr>
            <td class="text-center er-td">{{ $i + 1 }}</td>
            <td class="text-center er-td">{{ $r['admission_number'] ?? '' }}</td>
            <td class="er-td fw-semibold">{{ $r['name'] ?? '' }}</td>
            @foreach($subjects as $s)
              @php
                $sid = $s['id'];
                $score = data_get($r, "subject_scores.$sid");
              @endphp
              <td class="text-center er-td mark-sheet-score">{{ $score !== null && $score !== '' ? $score : '—' }}</td>
            @endforeach
            <td class="text-center er-td mark-sheet-score">{{ $r['total'] ?? '—' }}</td>
            <td class="text-center er-td mark-sheet-score">{{ $r['average'] ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="er-td fw-semibold">Subject average</td>
          @foreach($subjects as $s)
            @php $sid = $s['id']; @endphp
            <td class="text-center er-td fw-semibold">{{ $subjectAverages[$sid] ?? '—' }}</td>
          @endforeach
          <td class="text-center er-td"></td>
          <td class="text-center er-td fw-bold">{{ $classMean ?? '—' }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
@endif
