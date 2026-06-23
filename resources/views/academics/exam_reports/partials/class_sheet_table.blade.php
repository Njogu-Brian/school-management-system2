@php
  use App\Support\CbcGradePresentation;

  $subjects = $payload['subjects'] ?? [];
  $rows = $payload['rows'] ?? [];
  $meta = $payload['meta'] ?? [];
  $showStreamColumn = $showStreamColumn ?? true;
  $classroomId = (int) ($meta['classroom']['id'] ?? 0);
  $streamFiltered = ! empty($meta['stream_id']);

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
  <table class="table table-modern align-middle mb-0 table-sm class-sheet-data-table exam-report-marks-table">
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
          <th class="text-center er-th">{{ $streamFiltered ? 'Str pos' : 'Stream' }}</th>
        @endif
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $r)
        @php
          $avgGrade = CbcGradePresentation::forPercentage(
            is_numeric($r['average'] ?? null) ? (float) $r['average'] : null,
            $classroomId ?: null
          );
        @endphp
        <tr>
          <td class="text-center er-td">{{ $i + 1 }}</td>
          <td class="text-center er-td">{{ $r['admission_number'] ?? '' }}</td>
          <td class="er-td">
            <div class="fw-semibold">{{ $r['name'] ?? '' }}</div>
            @if($showStreamColumn && ! $streamFiltered && ! empty($r['stream_name']))
              <span class="mark-sheet-stream-pill">{{ $r['stream_name'] }}</span>
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
            <td class="text-center er-td">
              <div class="mark-sheet-score">{{ $score !== null && $score !== '' ? $score : '—' }}</div>
              @if($scoreGrade)
                @include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $scoreGrade])
              @endif
            </td>
          @endforeach
          <td class="text-center er-td mark-sheet-score">{{ $r['total'] ?? '—' }}</td>
          <td class="text-center er-td">
            <div class="mark-sheet-score">{{ $r['average'] ?? '—' }}</div>
            @if($avgGrade)
              @include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $avgGrade, 'wide' => true])
            @endif
          </td>
          <td class="text-center er-td fw-semibold">{{ $r['class_position'] ?? $r['position'] ?? '—' }}</td>
          @if($showStreamColumn)
            <td class="text-center er-td text-muted">
              @if($streamFiltered)
                {{ $r['stream_position'] ?? '—' }}
              @else
                {{ $r['stream_name'] ?? '—' }}
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
            <td class="text-center er-td">
              <div class="fw-semibold">{{ isset($subAvg) ? $subAvg : '—' }}</div>
              @if($subAvgGrade)
                @include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $subAvgGrade, 'wide' => true])
              @endif
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
          <td class="text-center er-td">
            <div class="fw-bold fs-6">{{ $classMean ?? '—' }}</div>
            @if($classMeanGrade)
              @include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $classMeanGrade, 'wide' => true])
            @endif
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
