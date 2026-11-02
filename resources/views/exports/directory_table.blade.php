<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Export' }}</title>
    <style>
        @page { margin: 18px 18px 52px 18px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
        .meta {
            color: #555;
            font-size: 8.5px;
            margin: 0 0 10px 0;
            padding: 6px 8px;
            background: #f7f7f7;
            border: 1px solid #e5e7eb;
        }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td {
            border: 1px solid #c9cdd4;
            padding: 5px 6px;
            vertical-align: top;
            word-break: break-word;
        }
        table.data th {
            background: #1f2937;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 700;
        }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #555;
            border-top: 1px solid #333;
            padding-top: 6px;
        }
        .footer .right { float: right; }
    </style>
</head>
<body>
    @php
      $reportTitle = $title ?? 'Export';
      $reportSubtitle = $subtitle ?? null;
      $generatedAt = isset($generated_at) ? \Illuminate\Support\Carbon::parse($generated_at) : now();
      $generatedBy = $generated_by ?? (auth()->user()?->name ?? 'System');
      $schoolName = $branding['school_name'] ?? setting('school_name', config('app.name'));
    @endphp

    @include('academics.exam_reports.partials.report_letterhead', [
        'variant' => 'pdf',
        'reportTitle' => $reportTitle,
        'reportSubtitle' => $reportSubtitle,
        'generatedAt' => $generatedAt,
        'generatedBy' => $generatedBy,
        'branding' => $branding ?? [],
    ])

    @if(!empty($recordCount))
      <div class="meta">
        {{ number_format($recordCount) }} record{{ (int) $recordCount === 1 ? '' : 's' }}
        · Generated {{ $generatedAt->format('d M Y, H:i') }}
        @if(!empty($generatedBy))
          by {{ $generatedBy }}
        @endif
      </div>
    @endif

    <table class="data">
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
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
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
        <span>{{ $schoolName }}</span>
        <span class="right">Confidential · {{ $reportTitle }}</span>
    </div>
</body>
</html>
