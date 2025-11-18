<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Scheme of Work - {{ $scheme->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 30%;
            background-color: #f5f5f5;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h3 {
            background-color: #3a1a59;
            color: white;
            padding: 10px;
            margin: 0;
        }
        .section-content {
            padding: 15px;
            border: 1px solid #ddd;
        }
        .strands-list {
            list-style: none;
            padding: 0;
        }
        .strands-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branding['school_name'] ?? 'School Name' }}</h1>
        <h2>Scheme of Work</h2>
        <p>{{ $scheme->title }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>Subject:</td>
            <td>{{ $scheme->subject->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Classroom:</td>
            <td>{{ $scheme->classroom->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Academic Year:</td>
            <td>{{ $scheme->academicYear->year ?? '' }}</td>
        </tr>
        <tr>
            <td>Term:</td>
            <td>{{ $scheme->term->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Status:</td>
            <td>{{ ucfirst($scheme->status) }}</td>
        </tr>
        <tr>
            <td>Progress:</td>
            <td>{{ $scheme->lessons_completed }} of {{ $scheme->total_lessons }} lessons ({{ $scheme->progress_percentage }}%)</td>
        </tr>
        <tr>
            <td>Created By:</td>
            <td>{{ $scheme->creator->first_name ?? '' }} {{ $scheme->creator->last_name ?? '' }}</td>
        </tr>
        @if($scheme->approved_at)
        <tr>
            <td>Approved By:</td>
            <td>{{ $scheme->approver->first_name ?? '' }} {{ $scheme->approver->last_name ?? '' }}</td>
        </tr>
        <tr>
            <td>Approved On:</td>
            <td>{{ $scheme->approved_at->format('d M Y') }}</td>
        </tr>
        @endif
    </table>

    @if($scheme->description)
    <div class="section">
        <h3>Description</h3>
        <div class="section-content">
            <p>{{ $scheme->description }}</p>
        </div>
    </div>
    @endif

    @if($scheme->strands_coverage && count($scheme->strands_coverage) > 0)
    <div class="section">
        <h3>Strands Coverage</h3>
        <div class="section-content">
            <ul class="strands-list">
                @foreach($scheme->strands_coverage as $strandId)
                    @php
                        $strand = \App\Models\Academics\CBCStrand::find($strandId);
                    @endphp
                    @if($strand)
                    <li>{{ $strand->code }} - {{ $strand->name }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($scheme->general_remarks)
    <div class="section">
        <h3>General Remarks</h3>
        <div class="section-content">
            <p>{{ $scheme->general_remarks }}</p>
        </div>
    </div>
    @endif

    @if($scheme->lessonPlans && $scheme->lessonPlans->count() > 0)
    <div class="section">
        <h3>Lesson Plans ({{ $scheme->lessonPlans->count() }})</h3>
        <div class="section-content">
            <table class="info-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Planned Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheme->lessonPlans as $plan)
                    <tr>
                        <td>{{ $plan->title }}</td>
                        <td>{{ $plan->planned_date->format('d M Y') }}</td>
                        <td>{{ ucfirst($plan->status) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generated_at ?? now()->format('d M Y H:i:s') }} by {{ $generated_by ?? 'System' }}</p>
        <p>{{ $branding['school_name'] ?? 'School Name' }} - {{ $branding['school_address'] ?? '' }}</p>
    </div>
</body>
</html>

