<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Class mark sheet</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
    h1 { font-size: 14px; margin: 0 0 6px 0; }
    h2 { font-size: 11px; margin: 16px 0 6px 0; border-bottom: 1px solid #333; padding-bottom: 4px; }
    .muted { color: #555; font-size: 8px; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { border: 1px solid #999; padding: 3px 4px; text-align: left; }
    th { background: #eee; font-weight: bold; }
    .num { text-align: center; }
    .page { page-break-after: always; }
    .page:last-child { page-break-after: auto; }
  </style>
</head>
<body>
  @foreach($bundles as $bundle)
    @if(!empty($bundle['payload']))
    @php
      $payload = $bundle['payload'];
      $subjects = $payload['subjects'] ?? [];
      $rows = $payload['rows'] ?? [];
      $meta = $payload['meta'] ?? [];
    @endphp
    <div class="page">
      <h1>Class mark sheet</h1>
      <div class="muted">
        {{ $bundle['classroom']->name ?? '' }}
        @if(($meta['mode'] ?? '') === 'exam_session')
          · {{ $meta['exam_session']['name'] ?? '' }}
        @elseif(($meta['mode'] ?? '') === 'subject_paper')
          · {{ $meta['subject']['name'] ?? '' }}
        @elseif(($meta['mode'] ?? '') === 'term')
          · Term summary
        @endif
      </div>
      <table>
        <thead>
          <tr>
            <th class="num">#</th>
            <th>Adm</th>
            <th>Student</th>
            @foreach($subjects as $s)
              <th class="num">{{ $s['code'] ?: \Illuminate\Support\Str::limit($s['name'], 6) }}</th>
              <th class="num">P</th>
            @endforeach
            <th class="num">Tot</th>
            <th class="num">Avg</th>
            <th class="num">C</th>
            <th class="num">S</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $r)
            <tr>
              <td class="num">{{ $i + 1 }}</td>
              <td>{{ $r['admission_number'] ?? '' }}</td>
              <td>{{ $r['name'] ?? '' }}</td>
              @foreach($subjects as $s)
                @php $sid = $s['id']; @endphp
                <td class="num">{{ data_get($r, "subject_scores.$sid") }}</td>
                <td class="num">{{ data_get($r, "subject_positions.$sid") }}</td>
              @endforeach
              <td class="num">{{ $r['total'] }}</td>
              <td class="num">{{ $r['average'] }}</td>
              <td class="num">{{ $r['class_position'] ?? $r['position'] }}</td>
              <td class="num">{{ $r['stream_position'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  @endforeach
</body>
</html>
