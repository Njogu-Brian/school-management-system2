<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lesson Plan - {{ $lesson_plan->title }}</title>
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
        .objectives-list {
            list-style: none;
            padding: 0;
        }
        .objectives-list li {
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
        <h2>Lesson Plan</h2>
        <p>{{ $lesson_plan->title }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>Subject:</td>
            <td>{{ $lesson_plan->subject->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Classroom:</td>
            <td>{{ $lesson_plan->classroom->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Planned Date:</td>
            <td>{{ $lesson_plan->planned_date->format('l, d M Y') }}</td>
        </tr>
        @if($lesson_plan->actual_date)
        <tr>
            <td>Actual Date:</td>
            <td>{{ $lesson_plan->actual_date->format('l, d M Y') }}</td>
        </tr>
        @endif
        <tr>
            <td>Duration:</td>
            <td>{{ $lesson_plan->duration_minutes }} minutes</td>
        </tr>
        <tr>
            <td>Status:</td>
            <td>{{ ucfirst($lesson_plan->status) }}</td>
        </tr>
        @if($lesson_plan->substrand)
        <tr>
            <td>CBC Substrand:</td>
            <td>{{ $lesson_plan->substrand->strand->name ?? '' }} - {{ $lesson_plan->substrand->name }}</td>
        </tr>
        @endif
    </table>

    @if($lesson_plan->learning_objectives && count($lesson_plan->learning_objectives) > 0)
    <div class="section">
        <h3>Learning Objectives</h3>
        <div class="section-content">
            <ul class="objectives-list">
                @foreach($lesson_plan->learning_objectives as $objective)
                    <li>{{ is_array($objective) ? ($objective['text'] ?? '') : $objective }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($lesson_plan->learning_outcomes)
    <div class="section">
        <h3>Learning Outcomes</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->learning_outcomes }}</p>
        </div>
    </div>
    @endif

    @if($lesson_plan->introduction)
    <div class="section">
        <h3>Introduction</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->introduction }}</p>
        </div>
    </div>
    @endif

    @if($lesson_plan->lesson_development)
    <div class="section">
        <h3>Lesson Development</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->lesson_development }}</p>
        </div>
    </div>
    @endif

    @if($lesson_plan->activities && count($lesson_plan->activities) > 0)
    <div class="section">
        <h3>Activities</h3>
        <div class="section-content">
            <ul class="objectives-list">
                @foreach($lesson_plan->activities as $activity)
                    <li>{{ is_array($activity) ? ($activity['text'] ?? '') : $activity }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($lesson_plan->assessment)
    <div class="section">
        <h3>Assessment</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->assessment }}</p>
        </div>
    </div>
    @endif

    @if($lesson_plan->conclusion)
    <div class="section">
        <h3>Conclusion</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->conclusion }}</p>
        </div>
    </div>
    @endif

    @if($lesson_plan->reflection)
    <div class="section">
        <h3>Reflection</h3>
        <div class="section-content">
            <p>{{ $lesson_plan->reflection }}</p>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generated_at ?? now()->format('d M Y H:i:s') }} by {{ $generated_by ?? 'System' }}</p>
        <p>{{ $branding['school_name'] ?? 'School Name' }} - {{ $branding['school_address'] ?? '' }}</p>
    </div>
</body>
</html>

