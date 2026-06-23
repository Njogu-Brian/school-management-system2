@php
  use App\Support\CbcGradePresentation;

  $subjects = $payload['subjects'] ?? [];
  $rows = $payload['rows'] ?? [];
  $meta = $payload['meta'] ?? [];
  $classroomId = (int) ($meta['classroom']['id'] ?? 0);
  $streamFiltered = ! empty($meta['stream_id']);
  $streamNamesById = $meta['stream_names_by_id'] ?? [];

  $resolveStreamName = function (array $r) use ($streamNamesById): ?string {
    if (! empty($r['stream_name'])) {
      return $r['stream_name'];
    }
    $sid = $r['stream_id'] ?? null;
    return ($sid && isset($streamNamesById[$sid])) ? $streamNamesById[$sid] : null;
  };

  $hasStreamInRows = collect($rows)->contains(fn ($r) => filled($resolveStreamName($r)));
  $showStreamColumn = ($showStreamColumn ?? false) && ($streamFiltered || $hasStreamInRows);

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

  $summaryColspan = 3 + count($subjects) + 2 + ($showStreamColumn ? 1 : 0);
@endphp
<div class="table-responsive">
  <table class="table table-modern align-middle mb-0 table-sm class-sheet-data-table exam-report-marks-table {{ $densityClass }} {{ $fitOnePageClass }}">
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
  </table>
</div>
