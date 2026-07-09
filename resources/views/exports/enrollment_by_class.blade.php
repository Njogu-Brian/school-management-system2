<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Enrollment by Class' }}</title>
    <style>
        @page { margin: 16px 16px 48px 16px; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .meta { color: #6b7280; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; letter-spacing: 0.02em; }
        td.num { text-align: right; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tfoot td { background: #eef2ff; font-weight: bold; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    @php
      $reportTitle = $title ?? 'Enrollment by Class';
      $reportSubtitle = $subtitle ?? null;
      $generatedAt = isset($generated_at) ? \Illuminate\Support\Carbon::parse($generated_at) : now();
      $generatedBy = $generated_by ?? (auth()->user()?->name ?? 'System');
    @endphp

    @include('academics.exam_reports.partials.report_letterhead', [
        'variant' => 'pdf',
        'reportTitle' => $reportTitle,
        'reportSubtitle' => $reportSubtitle,
        'generatedAt' => $generatedAt,
        'generatedBy' => $generatedBy,
    ])

    @if(!empty($recordCount))
      <div class="meta">
        {{ number_format($recordCount) }} class{{ $recordCount === 1 ? '' : 'es' }}
      </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $index => $cell)
                        <td class="{{ $index > 0 ? 'num' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($headers ?? []), 1) }}" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $branding['school_name'] ?? config('app.name') }}
    </div>
</body>
</html>
