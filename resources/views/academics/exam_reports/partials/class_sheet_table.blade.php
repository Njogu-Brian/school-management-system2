@php
  $subjects = $payload['subjects'] ?? [];
  $rows = $payload['rows'] ?? [];
  $meta = $payload['meta'] ?? [];

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
      <col class="er-col er-col--str">
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
        <th class="text-center er-th">Str</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $r)
        <tr>
          <td class="text-center er-td">{{ $i + 1 }}</td>
          <td class="text-center er-td">{{ $r['admission_number'] ?? '' }}</td>
          <td class="er-td fw-semibold">{{ $r['name'] ?? '' }}</td>
          @foreach($subjects as $s)
            @php $sid = $s['id']; @endphp
            <td class="text-center er-td">{{ data_get($r, "subject_scores.$sid") }}</td>
          @endforeach
          <td class="text-center er-td fw-semibold">{{ $r['total'] }}</td>
          <td class="text-center er-td">{{ $r['average'] }}</td>
          <td class="text-center er-td fw-semibold">{{ $r['class_position'] ?? $r['position'] }}</td>
          <td class="text-center er-td text-muted">{{ $r['stream_position'] }}</td>
        </tr>
      @empty
        <tr><td colspan="{{ 7 + count($subjects) }}" class="text-center text-muted py-4">No rows.</td></tr>
      @endforelse
    </tbody>
    @if(!empty($rows))
      <tfoot>
        <tr>
          <td colspan="3" class="er-td fw-semibold">Subject average</td>
          @foreach($subjects as $s)
            @php $sid = $s['id']; @endphp
            <td class="text-center er-td fw-semibold">{{ $subjectAverages[$sid] ?? '' }}</td>
          @endforeach
          <td class="text-center er-td fw-semibold"></td>
          <td class="text-center er-td fw-semibold"></td>
          <td class="text-center er-td fw-semibold"></td>
          <td class="text-center er-td fw-semibold"></td>
        </tr>
      </tfoot>
    @endif
  </table>
</div>
