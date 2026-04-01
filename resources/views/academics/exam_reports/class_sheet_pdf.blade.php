<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Class mark sheet</title>
  <style>
    @page { margin: 10mm 12mm; size: A4 landscape; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #111; margin: 0; }
    .page-break { page-break-after: always; }
    .page-break:last-child { page-break-after: auto; }
    .exam-report-marks-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-top: 4px;
    }
    .exam-report-marks-table thead { display: table-header-group; }
    .exam-report-marks-table th {
      background: #e5e5e5;
      border: 1px solid #333;
      padding: 4px 3px;
      font-weight: bold;
      text-align: center;
      vertical-align: middle;
      word-wrap: break-word;
      word-break: break-word;
      line-height: 1.1;
    }
    .exam-report-marks-table td {
      border: 1px solid #555;
      padding: 3px 3px;
      vertical-align: middle;
      word-wrap: break-word;
      word-break: break-word;
      line-height: 1.15;
    }
    .exam-report-marks-table .num { text-align: center; }
    .exam-report-marks-table .left { text-align: left; }
    .exam-report-marks-table tbody tr { page-break-inside: avoid; }
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
        $sub = null;
        if (($meta['mode'] ?? '') === 'exam_session') {
            $sub = $meta['exam_session']['name'] ?? null;
        } elseif (($meta['mode'] ?? '') === 'subject_paper') {
            $sub = $meta['subject']['name'] ?? null;
        } elseif (($meta['mode'] ?? '') === 'term') {
            $sub = 'Term overview';
        }
        $subtitle = trim(($bundle['classroom']->name ?? 'Class').($sub ? ' — '.$sub : ''));
      @endphp
      <div class="page-break">
        @include('academics.exam_reports.partials.report_letterhead', [
          'variant' => 'pdf',
          'reportTitle' => 'Class Mark Sheet',
          'reportSubtitle' => $subtitle,
          'generatedAt' => $generatedAt ?? now(),
          'generatedBy' => $generatedBy ?? 'System',
        ])
        <table class="exam-report-marks-table">
          <colgroup>
            <col style="width:3%;">
            <col style="width:7.5%;">
            <col style="width:20%;">
            @foreach($subjects as $s)
              <col style="width:5.2%;">
            @endforeach
            <col style="width:5.8%;">
            <col style="width:5.2%;">
            <col style="width:4%;">
            <col style="width:4%;">
          </colgroup>
          <thead>
            <tr>
              <th>#</th>
              <th>Adm<br>No</th>
              <th class="left">Stud<br>ent</th>
              @foreach($subjects as $s)
                <th>{{ $s['code'] ?: \Illuminate\Support\Str::limit($s['name'], 5) }}</th>
              @endforeach
              <th>Total</th>
              <th>Avg</th>
              <th>Cls</th>
              <th>Str</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $i => $r)
              <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="num">{{ $r['admission_number'] ?? '' }}</td>
                <td class="left">{{ $r['name'] ?? '' }}</td>
                @foreach($subjects as $s)
                  @php $sid = $s['id']; @endphp
                  <td class="num">{{ data_get($r, "subject_scores.$sid") }}</td>
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
