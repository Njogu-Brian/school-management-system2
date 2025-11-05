@php
// Simple print styles
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $dto['student']['name'] ?? '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        .header, .footer { width: 100%; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .muted { color: #666; }
        .mb-1{ margin-bottom: 6px; } .mb-2{ margin-bottom: 10px; } .mb-3{ margin-bottom: 16px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #444; padding: 6px; }
        .table th { background: #f3f3f3; text-align: left; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .box { border: 1px solid #999; padding: 8px; }
        .text-right { text-align: right; }
        .small { font-size: 11px; }
        .center { text-align: center; }
        .logo { height: 60px; }
        .sign { height: 50px; border-bottom: 1px solid #999; }
        .pt-2{ padding-top: 10px; } .mt-2{ margin-top: 10px; }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['dto' => $dto, 'title' => 'Academic Report'])

    {{-- Student & term info --}}
    <div class="mb-2">
        <table class="table" style="border: 1px solid #444;">
            <tr>
                <td><strong>Student:</strong> {{ $dto['student']['name'] }}</td>
                <td><strong>Adm No:</strong> {{ $dto['student']['admission_number'] }}</td>
                <td><strong>Class:</strong> {{ $dto['student']['class'] }} {{ $dto['student']['stream'] ? '— '.$dto['student']['stream'] : '' }}</td>
                <td><strong>Term/Year:</strong> {{ $dto['context']['term'] }} / {{ $dto['context']['year'] }}</td>
            </tr>
        </table>
    </div>

    {{-- Subjects table: columns are all exams in the term + Term Avg + Grade --}}
    @php
        // Build columns from the first subject row (safe if exists)
        $examHeaders = collect($dto['subjects'])->first()['exams'] ?? [];
    @endphp
    <table class="table mb-3">
        <thead>
            <tr>
                <th>Subject</th>
                @foreach($examHeaders as $eh)
                    <th>{{ $eh['exam_name'] }}</th>
                @endforeach
                <th>Term Avg</th>
                <th>Grade</th>
                <th>Teacher Remark</th>
            </tr>
        </thead>
        <tbody>
        @forelse($dto['subjects'] as $row)
            <tr>
                <td>{{ $row['subject_name'] }}</td>
                @foreach($row['exams'] as $ex)
                    <td class="center">{{ $ex['score'] !== null ? number_format($ex['score'],2) : '—' }}</td>
                @endforeach
                <td class="center"><strong>{{ $row['term_avg'] !== null ? number_format($row['term_avg'],2) : '—' }}</strong></td>
                <td class="center">{{ $row['grade_label'] ?? '' }}</td>
                <td>{{ $row['teacher_remark'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ 3 + count($examHeaders) }}" class="center">No subject marks.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Skills & Attendance/Behaviour --}}
    <div class="grid">
        <div class="box">
            <strong>Skills</strong>
            <table class="table small">
                <thead><tr><th>Skill</th><th>Grade</th><th>Comment</th></tr></thead>
                <tbody>
                    @forelse($dto['skills'] as $s)
                        <tr>
                            <td>{{ $s['skill'] }}</td>
                            <td class="center">{{ $s['grade'] }}</td>
                            <td>{{ $s['comment'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="center">No skills graded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="box">
            <strong>Attendance & Behaviour</strong>
            <table class="table small">
                <tr>
                    <td style="width:40%"><strong>Attendance</strong></td>
                    <td>Present: {{ $dto['attendance']['present'] ?? 0 }}</td>
                    <td>Late: {{ $dto['attendance']['late'] ?? 0 }}</td>
                    <td>Absent: {{ $dto['attendance']['absent'] ?? 0 }}</td>
                    <td>%: {{ $dto['attendance']['percent'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Behaviour</strong></td>
                    <td colspan="4">
                        Total: {{ $dto['behavior']['count'] ?? 0 }},
                        +ve: {{ $dto['behavior']['positive'] ?? 0 }},
                        -ve: {{ $dto['behavior']['negative'] ?? 0 }}
                    </td>
                </tr>
            </table>

            @if(!empty($dto['behavior']['latest']))
                <div class="small mt-2">
                    <strong>Recent behaviour notes:</strong>
                    <ul>
                        @foreach($dto['behavior']['latest'] as $b)
                            <li>{{ $b['date'] }} — {{ $b['name'] }} ({{ $b['type'] }}): {{ $b['notes'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Comments --}}
    <div class="grid mt-2">
        <div class="box">
            <strong>Class Teacher’s Remark</strong>
            <div class="pt-2">{{ $dto['comments']['teacher_remark'] ?? '' }}</div>
        </div>
        <div class="box">
            <strong>Head Teacher’s Remark</strong>
            <div class="pt-2">{{ $dto['comments']['headteacher_remark'] ?? '' }}</div>
        </div>
    </div>

    <div class="grid mt-2">
        <div class="box">
            <strong>Career Interest</strong>
            <div class="pt-2">{{ $dto['comments']['career_interest'] ?? '' }}</div>
        </div>
        <div class="box">
            <strong>Talent Noticed</strong>
            <div class="pt-2">{{ $dto['comments']['talent_noticed'] ?? '' }}</div>
        </div>
    </div>

    @include('pdf.partials.footer', ['dto' => $dto])
</body>
</html>
