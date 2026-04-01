@php
  $subjects = $payload['subjects'] ?? [];
  $rows = $payload['rows'] ?? [];
  $meta = $payload['meta'] ?? [];
@endphp
<div class="table-responsive">
  <table class="table table-modern align-middle mb-0 table-sm class-sheet-data-table">
    <thead class="table-light">
      <tr>
        <th style="min-width:50px">#</th>
        <th style="min-width:100px">Adm No</th>
        <th style="min-width:180px">Student</th>
        @foreach($subjects as $s)
          <th style="min-width:72px">{{ $s['code'] ? $s['code'] : $s['name'] }}</th>
          <th style="min-width:48px">Pos</th>
        @endforeach
        <th style="min-width:72px">Total</th>
        <th style="min-width:72px">Avg</th>
        <th style="min-width:64px">Cls</th>
        <th style="min-width:64px">Str</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $r)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $r['admission_number'] ?? '' }}</td>
          <td class="fw-semibold">{{ $r['name'] ?? '' }}</td>
          @foreach($subjects as $s)
            @php $sid = $s['id']; @endphp
            <td>{{ data_get($r, "subject_scores.$sid") }}</td>
            <td class="text-muted small">{{ data_get($r, "subject_positions.$sid") }}</td>
          @endforeach
          <td class="fw-semibold">{{ $r['total'] }}</td>
          <td>{{ $r['average'] }}</td>
          <td class="fw-semibold">{{ $r['class_position'] ?? $r['position'] }}</td>
          <td class="text-muted">{{ $r['stream_position'] }}</td>
        </tr>
      @empty
        <tr><td colspan="{{ 7 + (count($subjects) * 2) }}" class="text-center text-muted py-4">No rows.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
