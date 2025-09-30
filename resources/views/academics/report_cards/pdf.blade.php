<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report Card</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .title { text-align:center; font-size: 18px; font-weight:700; margin-bottom: 8px; }
    .muted { color:#666; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #999; padding:6px 8px; }
    th { background:#f0f0f0; }
    .legend { margin-top: 12px; }
    .legend span { display:inline-block; margin-right:12px; }
  </style>
</head>
<body>
  <div class="title">ROYAL KINGS SCHOOL – PROGRESS RECORDS</div>
  <p><strong>Child’s Name:</strong> {{ $rc->student->full_name }} &nbsp;
     <strong>Class:</strong> {{ optional($rc->classroom)->name }} &nbsp;
     <strong>Term/Year:</strong> {{ optional($rc->term)->name }} / {{ optional($rc->academicYear)->year }}</p>

  <table>
    <thead><tr>
      <th>Learning Area</th><th>Opener</th><th>Midterm</th><th>End Term</th><th>Band</th><th>Remarks</th>
    </tr></thead>
    <tbody>
      @foreach($rc->marks as $m)
      <tr>
        <td>{{ optional($m->subject)->name }}</td>
        <td>{{ $m->opener_score }}</td>
        <td>{{ $m->midterm_score }}</td>
        <td>{{ $m->endterm_score }}</td>
        <td>{{ $m->grade_label }}</td>
        <td>{{ $m->subject_remark }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="legend">
    @foreach($legend as $k=>$v)
      <span><strong>{{ $k }}</strong> = {{ $v }}</span>
    @endforeach
  </div>

  <h4>Personal Growth and Social Skills</h4>
  <table>
    <thead><tr><th>Skill</th><th>Rating</th></tr></thead>
    <tbody>
      @foreach($rc->skills as $s)
        <tr><td>{{ $s->skill_name }}</td><td>{{ $s->rating }}</td></tr>
      @endforeach
    </tbody>
  </table>

  <p><strong>Career of Interest:</strong> {{ $rc->career_interest }}</p>
  <p><strong>Gifts / Talent Noticed:</strong> {{ $rc->talent_noticed }}</p>
  <p><strong>Class Facilitator Remarks:</strong> {{ $rc->teacher_remark }}</p>
  <p><strong>Headteacher:</strong> {{ $rc->headteacher_remark }}</p>
</body>
</html>
