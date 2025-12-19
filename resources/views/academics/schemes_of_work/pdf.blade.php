<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Scheme of Work - {{ $scheme->title }}</title>
    <style>
        :root {
            --ink: #1f2937;
            --muted: #4b5563;
            --border: #e5e7eb;
            --accent: #4f46e5;
            --bg: #f9fafb;
        }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: var(--ink); background: var(--bg); }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid var(--ink); padding-bottom: 16px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header h2 { margin: 4px 0; font-size: 16px; color: var(--muted); }
        .info-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .info-table td { padding: 8px; border: 1px solid var(--border); }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f3f4f6; }
        .section { margin-bottom: 20px; }
        .section h3 { background-color: var(--accent); color: #fff; padding: 8px 10px; margin: 0; font-size: 13px; }
        .section-content { padding: 10px 12px; border: 1px solid var(--border); background: #fff; }
        .strands-list { list-style: none; padding: 0; margin: 0; }
        .strands-list li { padding: 6px 0; border-bottom: 1px solid #eef2f7; }
        .strands-list li:last-child { border-bottom: none; }
        .plans-table { width: 100%; border-collapse: collapse; }
        .plans-table th, .plans-table td { padding: 8px; border: 1px solid var(--border); text-align: left; }
        .plans-table th { background: #f3f4f6; font-weight: 600; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: var(--muted); border-top: 1px solid var(--border); padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branding['school_name'] ?? 'School Name' }}</h1>
        <h2>Scheme of Work</h2>
        <p>{{ $scheme->title }}</p>
    </div>

    <table class="info-table">
        <tr><td>Subject:</td><td>{{ $scheme->subject->name ?? '' }}</td></tr>
        <tr><td>Classroom:</td><td>{{ $scheme->classroom->name ?? '' }}</td></tr>
        <tr><td>Academic Year:</td><td>{{ $scheme->academicYear->year ?? '' }}</td></tr>
        <tr><td>Term:</td><td>{{ $scheme->term->name ?? '' }}</td></tr>
        <tr><td>Status:</td><td>{{ ucfirst($scheme->status) }}</td></tr>
        <tr><td>Progress:</td><td>{{ $scheme->lessons_completed }} of {{ $scheme->total_lessons }} lessons ({{ $scheme->progress_percentage }}%)</td></tr>
        <tr><td>Created By:</td><td>{{ $scheme->creator->first_name ?? '' }} {{ $scheme->creator->last_name ?? '' }}</td></tr>
        @if($scheme->approved_at)
        <tr><td>Approved By:</td><td>{{ $scheme->approver->first_name ?? '' }} {{ $scheme->approver->last_name ?? '' }}</td></tr>
        <tr><td>Approved On:</td><td>{{ $scheme->approved_at->format('d M Y') }}</td></tr>
        @endif
    </table>

    @if($scheme->description)
    <div class="section">
        <h3>Description</h3>
        <div class="section-content"><p>{{ $scheme->description }}</p></div>
    </div>
    @endif

    @if($scheme->strands_coverage && count($scheme->strands_coverage) > 0)
    <div class="section">
        <h3>Strands Coverage</h3>
        <div class="section-content">
            <ul class="strands-list">
                @foreach($scheme->strands_coverage as $strandId)
                    @php $strand = \App\Models\Academics\CBCStrand::find($strandId); @endphp
                    @if($strand)<li>{{ $strand->code }} - {{ $strand->name }}</li>@endif
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($scheme->general_remarks)
    <div class="section">
        <h3>General Remarks</h3>
        <div class="section-content"><p>{{ $scheme->general_remarks }}</p></div>
    </div>
    @endif

    @if($scheme->lessonPlans && $scheme->lessonPlans->count() > 0)
    <div class="section">
        <h3>Lesson Plans ({{ $scheme->lessonPlans->count() }})</h3>
        <div class="section-content">
            <table class="plans-table">
                <thead><tr><th>Title</th><th>Planned Date</th><th>Status</th></tr></thead>
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
