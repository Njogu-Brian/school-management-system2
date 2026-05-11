<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $votehead->name }} — Roster</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, sans-serif; margin: 1.5rem; color: #222; }
    h1 { font-size: 1.25rem; margin: 0 0 0.25rem; }
    .meta { color: #555; font-size: 0.9rem; margin-bottom: 1rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
    th, td { border: 1px solid #ccc; padding: 0.4rem 0.5rem; text-align: left; }
    th { background: #f5f5f5; }
    @media print {
      body { margin: 0.5rem; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <p class="no-print"><button type="button" onclick="window.print()">Print</button></p>
  <h1>{{ $votehead->name }}</h1>
  <div class="meta">Year {{ $year }} — Term {{ $term }} — {{ $students->count() }} student(s)</div>
  <table>
    <thead>
      <tr>
        <th style="width:3rem">#</th>
        <th>Admission</th>
        <th>Name</th>
        <th>Class</th>
        <th style="width:30%">Signature / notes</th>
      </tr>
    </thead>
    <tbody>
      @foreach($students as $i => $student)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $student->admission_number }}</td>
          <td>{{ $student->full_name }}</td>
          <td>{{ $student->classroom->name ?? '—' }}</td>
          <td></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
