<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $report_card->student->full_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h1>Report Card - {{ $report_card->student->full_name }}</h1>

    <p><strong>Class:</strong> {{ $report_card->classroom->name ?? '' }} {{ $report_card->stream->name ?? '' }}</p>
    <p><strong>Term / Year:</strong> {{ $report_card->term->name ?? '' }} / {{ $report_card->academicYear->year ?? '' }}</p>

    <h3>Summary</h3>
    <p>{{ $report_card->summary ?? 'No summary provided.' }}</p>

    <h3>Teacher Remark</h3>
    <p>{{ $report_card->teacher_remark ?? '-' }}</p>

    <h3>Headteacher Remark</h3>
    <p>{{ $report_card->headteacher_remark ?? '-' }}</p>

    <h3>Skills</h3>
    <table>
        <thead>
            <tr>
                <th>Skill</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report_card->skills as $skill)
                <tr>
                    <td>{{ $skill->skill_name }}</td>
                    <td>{{ $skill->rating }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
