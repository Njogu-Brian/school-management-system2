@php
  $D = $dto ?? [];
  $examHeaders = data_get(collect($D['subjects'] ?? [])->first(), 'exams', []);
  $referenceTermName = $D['context']['reference_term'] ?? null;
  $cbcLegend = collect(\App\Support\CbcGradePresentation::standardBands())
    ->map(fn ($band) => $band['short'].' = '.$band['label'])
    ->implode(' | ');
  $cellStyle = 'padding:6px; border:1px solid #d1d5db;';
  $trendPoints = collect($D['year_trend']['points'] ?? []);
  $chartWidth = 680;
  $chartHeight = 220;
  $chartPaddingLeft = 42;
  $chartPaddingRight = 18;
  $chartPaddingTop = 18;
  $chartPaddingBottom = 42;
  $plotWidth = $chartWidth - $chartPaddingLeft - $chartPaddingRight;
  $plotHeight = $chartHeight - $chartPaddingTop - $chartPaddingBottom;
  $trendMin = (float) ($D['year_trend']['min'] ?? 0);
  $trendMax = (float) ($D['year_trend']['max'] ?? 100);
  $trendRange = max(1, $trendMax - $trendMin);
  $svgPoints = $trendPoints->values()->map(function ($point, $index) use ($trendPoints, $chartPaddingLeft, $chartPaddingTop, $plotWidth, $plotHeight, $trendMin, $trendRange) {
    $count = max(1, $trendPoints->count() - 1);
    $x = $chartPaddingLeft + ($count === 0 ? 0 : ($index / $count) * $plotWidth);
    $value = (float) ($point['value'] ?? 0);
    $y = $chartPaddingTop + $plotHeight - ((($value - $trendMin) / $trendRange) * $plotHeight);

    return [
      'label' => $point['label'] ?? '',
      'value' => $value,
      'x' => round($x, 2),
      'y' => round($y, 2),
    ];
  });
  $polylinePoints = $svgPoints->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
  $gridValues = [0, 20, 40, 60, 80, 100];
  $verticalBarMaxHeight = 150;
@endphp

{{-- School letterhead (logo, contact details, print timestamp) --}}
@include('academics.exam_reports.partials.report_letterhead', [
  'reportTitle' => 'Report Card',
  'reportSubtitle' => trim(($D['context']['term'] ?? '').' / '.($D['context']['year'] ?? '')),
  'generatedAt' => $D['generated']['at'] ?? now(),
  'generatedBy' => $D['generated']['by'] ?? (auth()->user()?->name ?? 'System'),
  'variant' => !empty($isPdf) ? 'pdf' : 'web-print',
])

{{-- Student & term --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:10px;">
  <tr style="background:#f9fafb;">
    <td style="{{ $cellStyle }}"><strong>Student:</strong> {{ $D['student']['name'] ?? '' }}</td>
    <td style="{{ $cellStyle }}"><strong>Adm No:</strong> {{ $D['student']['admission_number'] ?? '' }}</td>
    <td style="{{ $cellStyle }}"><strong>Class:</strong> {{ $D['student']['class'] ?? '' }} {{ !empty($D['student']['stream']) ? '— '.$D['student']['stream'] : '' }}</td>
    <td style="{{ $cellStyle }}"><strong>Term/Year:</strong> {{ $D['context']['term'] ?? '' }} / {{ $D['context']['year'] ?? '' }}</td>
  </tr>
</table>

{{-- Subjects --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:12px;">
  <thead>
    <tr style="background:#f3f4f6;">
      <th style="{{ $cellStyle }} text-align:left;">Subject</th>
      @if($referenceTermName)
        <th style="{{ $cellStyle }} text-align:center;">{{ $referenceTermName }} Avg</th>
        <th style="{{ $cellStyle }} text-align:center;">{{ $referenceTermName }} Grade</th>
      @endif
      @foreach($examHeaders as $eh)
        <th style="{{ $cellStyle }} text-align:center;">{{ $eh['exam_name'] }}</th>
      @endforeach
      <th style="{{ $cellStyle }} text-align:center;">Term Avg</th>
      <th style="{{ $cellStyle }} text-align:center;">Grade</th>
      <th style="{{ $cellStyle }} text-align:left;">Teacher Remark</th>
    </tr>
  </thead>
  <tbody>
    @forelse($D['subjects'] as $row)
      <tr style="background:#fff;">
        <td style="{{ $cellStyle }}">{{ $row['subject_name'] }}</td>
        @if($referenceTermName)
          <td style="{{ $cellStyle }} text-align:center;">
            <strong>{{ $row['reference_term_avg'] !== null ? number_format($row['reference_term_avg'], 2) : '—' }}</strong>
          </td>
          <td style="{{ $cellStyle }} text-align:center;">
            <strong>{{ $row['reference_grade_label'] ?? '—' }}</strong>
          </td>
        @endif
        @foreach($row['exams'] as $ex)
          <td style="{{ $cellStyle }} text-align:center;">
            @if($ex['score'] !== null)
              <div>{{ number_format($ex['score'], 2) }}</div>
              @if(!empty($ex['grade_label']))
                <div style="font-size:{{ !empty($isPdf) ? '8px' : '0.75rem' }}; color:#555;">{{ $ex['grade_label'] }}</div>
              @endif
            @else
              —
            @endif
          </td>
        @endforeach
        <td style="{{ $cellStyle }} text-align:center;"><strong>{{ $row['term_avg'] !== null ? number_format($row['term_avg'],2) : '—' }}</strong></td>
        <td style="{{ $cellStyle }} text-align:center;"><strong>{{ $row['grade_label'] ?? '—' }}</strong></td>
        <td style="{{ $cellStyle }}">{{ $row['teacher_remark'] ?? '' }}</td>
      </tr>
    @empty
      <tr><td colspan="{{ ($referenceTermName ? 5 : 3) + count($examHeaders) }}" style="padding:8px; text-align:center;">No subject marks.</td></tr>
    @endforelse
  </tbody>
</table>

{{-- Term overview --}}
<table style="width:100%; border-collapse:separate; border-spacing:10px 0; margin-bottom:12px;">
  <tr>
    @if($referenceTermName)
      <td style="width:50%; vertical-align:top;">
        <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
          <strong>{{ $D['overview']['reference_term']['name'] ?? $referenceTermName }} Reference</strong>
          <div style="margin-top:6px;">Average: <strong>{{ data_get($D, 'overview.reference_term.average') !== null ? number_format((float) data_get($D, 'overview.reference_term.average'), 2) : '—' }}</strong></div>
          <div>Grade: <strong>{{ data_get($D, 'overview.reference_term.grade') ?? '—' }}</strong></div>
        </div>
      </td>
      <td style="width:50%; vertical-align:top;">
        <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
          <strong>{{ $D['overview']['current_term']['name'] ?? ($D['context']['term'] ?? 'Current Term') }} Snapshot</strong>
          <div style="margin-top:6px;">Average: <strong>{{ data_get($D, 'overview.current_term.average') !== null ? number_format((float) data_get($D, 'overview.current_term.average'), 2) : '—' }}</strong></div>
          <div>Grade: <strong>{{ data_get($D, 'overview.current_term.grade') ?? '—' }}</strong></div>
        </div>
      </td>
    @else
      <td style="width:100%; vertical-align:top;">
        <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
          <strong>{{ $D['overview']['current_term']['name'] ?? ($D['context']['term'] ?? 'Current Term') }} Snapshot</strong>
          <div style="margin-top:6px;">Average: <strong>{{ data_get($D, 'overview.current_term.average') !== null ? number_format((float) data_get($D, 'overview.current_term.average'), 2) : '—' }}</strong></div>
          <div>Grade: <strong>{{ data_get($D, 'overview.current_term.grade') ?? '—' }}</strong></div>
        </div>
      </td>
    @endif
  </tr>
</table>

{{-- Academic year trend graph --}}
<div style="border:1px solid #d1d5db; background:#fff; padding:10px; margin-bottom:12px;">
  <div style="font-weight:700; margin-bottom:6px;">Academic Year Performance Trend</div>
  <div style="font-size:{{ !empty($isPdf) ? '9px' : '0.82rem' }}; color:#555; margin-bottom:8px;">
    Overall exam averages across the academic year, ordered by term and sitting.
  </div>
  @if($svgPoints->count() > 0)
    <table style="width:100%; border-collapse:collapse; margin-top:6px; table-layout:fixed;">
      <tbody>
        <tr>
          @foreach($svgPoints as $point)
            @php
              $val = (float) ($point['value'] ?? 0);
              $pct = ($val - (float) $trendMin) / max(1e-9, (float) $trendRange) * 100;
              $pct = max(0, min(100, $pct));
              $barHeight = max(12, (int) round(($pct / 100) * $verticalBarMaxHeight));
            @endphp
            <td style="padding:8px 6px; border:1px solid #e5e7eb; vertical-align:bottom; text-align:center;">
              <div style="height:18px; font-size:{{ !empty($isPdf) ? '9px' : '0.78rem' }}; font-weight:700; color:#111827; margin-bottom:6px;">
                {{ number_format($val, 1) }}
              </div>
              <div style="height:{{ $verticalBarMaxHeight }}px; position:relative; margin:0 auto 8px auto; width:40px; background:linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); border:1px solid #dbeafe; border-radius:14px; box-shadow: inset 0 2px 4px rgba(255,255,255,.85);">
                <div style="position:absolute; left:5px; right:5px; bottom:5px; height:{{ $barHeight }}px; background:linear-gradient(180deg, #8b5cf6 0%, #3b82f6 35%, #06b6d4 70%, #22c55e 100%); border-radius:10px; box-shadow: 0 4px 8px rgba(59,130,246,.25); overflow:hidden;">
                  <div style="height:6px; background:rgba(255,255,255,.38);"></div>
                  <div style="height:100%; background:linear-gradient(180deg, rgba(255,255,255,.10) 0%, rgba(0,0,0,.12) 100%);"></div>
                </div>
              </div>
              <div style="font-size:{{ !empty($isPdf) ? '8px' : '0.72rem' }}; color:#374151; line-height:1.25;">
                {{ $point['label'] }}
              </div>
            </td>
          @endforeach
        </tr>
      </tbody>
    </table>
  @else
    <div class="text-muted">No academic-year exam trend data available yet.</div>
  @endif
</div>

{{-- Attendance + Behaviour (Skills removed for now) --}}
<table style="width:100%; border-collapse:separate; border-spacing:10px 0;">
  <tr>
    <td style="width:100%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
        <strong>Attendance & Behaviour</strong>
        <table style="width:100%; margin-top:6px; border-collapse:collapse; border:1px solid #d1d5db;">
          <tr>
            <td style="padding:6px; border:1px solid #d1d5db; width:40%;"><strong>Attendance</strong></td>
            <td style="padding:6px; border:1px solid #d1d5db;">Present: {{ $D['attendance']['present'] ?? 0 }} / {{ $D['attendance']['total'] ?? ($D['attendance']['expected_school_days'] ?? 0) }}</td>
            <td style="padding:6px; border:1px solid #d1d5db;">Late: {{ $D['attendance']['late'] ?? 0 }}</td>
            <td style="padding:6px; border:1px solid #d1d5db;">Absent: {{ $D['attendance']['absent'] ?? 0 }}</td>
            <td style="padding:6px; border:1px solid #d1d5db;">%: {{ $D['attendance']['percent'] ?? 0 }}</td>
          </tr>
          <tr>
            <td style="padding:6px; border:1px solid #d1d5db;"><strong>Behaviour</strong></td>
            <td colspan="4" style="padding:6px; border:1px solid #d1d5db;">
              Total: {{ $D['behavior']['count'] ?? 0 }},
              +ve: {{ $D['behavior']['positive'] ?? 0 }},
              -ve: {{ $D['behavior']['negative'] ?? 0 }}
            </td>
          </tr>
        </table>
        @if(!empty($D['behavior']['latest']))
          <div style="font-size:11px; margin-top:6px;">
            <strong>Recent notes:</strong>
            <ul style="margin:6px 0 0 16px;">
              @foreach($D['behavior']['latest'] as $b)
                <li>{{ $b['date'] }} — {{ $b['name'] }} ({{ $b['type'] }}): {{ $b['notes'] }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>
    </td>
  </tr>
</table>

{{-- CBC Performance Level --}}
@if(!empty($D['cbc']['overall_performance_level']))
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-top:10px; margin-bottom:10px;">
  <tr style="background:#f3f4f6;">
    <th style="padding:6px; border:1px solid #d1d5db; text-align:left;">Overall Performance Level</th>
    <td style="padding:6px; border:1px solid #d1d5db;">
      <strong>{{ \App\Support\CbcGradePresentation::normalizeShortCode($D['cbc']['overall_performance_level'] ?? '') ?? 'N/A' }}</strong>
      @if(!empty($D['cbc']['overall_performance_level_name']))
        — {{ $D['cbc']['overall_performance_level_name'] }}
      @endif
    </td>
  </tr>
  <tr>
    <td colspan="2" style="padding:6px; border:1px solid #d1d5db; font-size:{{ !empty($isPdf) ? '8px' : '0.8rem' }}; color:#555;">
      {{ $cbcLegend }}
    </td>
  </tr>
</table>
@endif

{{-- CBC Core Competencies --}}
@if(!empty($D['cbc']['core_competencies']) && is_array($D['cbc']['core_competencies']) && count($D['cbc']['core_competencies']) > 0)
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:10px;">
  <thead>
    <tr style="background:#f3f4f6;">
      <th style="padding:6px; border:1px solid #d1d5db; text-align:left;" colspan="3">Core Competencies</th>
    </tr>
    <tr style="background:#f9fafb;">
      <th style="padding:6px; border:1px solid #d1d5db; text-align:left;">Competency</th>
      <th style="padding:6px; border:1px solid #d1d5db; text-align:center;">Code</th>
      <th style="padding:6px; border:1px solid #d1d5db; text-align:center;">Average Score</th>
    </tr>
  </thead>
  <tbody>
    @foreach($D['cbc']['core_competencies'] as $code => $competency)
      <tr>
        <td style="padding:6px; border:1px solid #d1d5db;">{{ $competency['name'] ?? $code }}</td>
        <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $code }}</td>
        <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $competency['average'] !== null ? number_format($competency['average'], 2) : 'N/A' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- CBC CAT Breakdown --}}
@php
  $hasCatData = !empty($D['cbc']['cat_breakdown']) && is_array($D['cbc']['cat_breakdown'])
    ? collect($D['cbc']['cat_breakdown'])->except('average')->filter(fn($v) => $v !== null)->isNotEmpty()
    : false;
@endphp
@if(!empty($hasCatData))
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:10px;">
  <thead>
    <tr style="background:#f3f4f6;"><th style="padding:6px; border:1px solid #d1d5db; text-align:left;" colspan="5">CAT Breakdown</th></tr>
    <tr style="background:#f9fafb;"><th style="padding:6px; border:1px solid #d1d5db; text-align:center;">CAT 1</th><th style="padding:6px; border:1px solid #d1d5db; text-align:center;">CAT 2</th><th style="padding:6px; border:1px solid #d1d5db; text-align:center;">CAT 3</th><th style="padding:6px; border:1px solid #d1d5db; text-align:center;">CAT 4</th><th style="padding:6px; border:1px solid #d1d5db; text-align:center;">Average</th></tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $D['cbc']['cat_breakdown']['cat1'] ?? 'N/A' }}</td>
      <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $D['cbc']['cat_breakdown']['cat2'] ?? 'N/A' }}</td>
      <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $D['cbc']['cat_breakdown']['cat3'] ?? 'N/A' }}</td>
      <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $D['cbc']['cat_breakdown']['cat4'] ?? 'N/A' }}</td>
      <td style="padding:6px; border:1px solid #d1d5db; text-align:center;">{{ $D['cbc']['cat_breakdown']['average'] ?? 'N/A' }}</td>
    </tr>
  </tbody>
</table>
@endif

{{-- Comments --}}
<table style="width:100%; border-collapse:separate; border-spacing:10px 0;">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
        <strong>Class Teacher’s Remark</strong>
        <div style="padding-top:6px;">{{ $D['comments']['teacher_remark'] ?? '' }}</div>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
        <strong>Head Teacher’s Remark</strong>
        <div style="padding-top:6px;">{{ $D['comments']['headteacher_remark'] ?? '' }}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
        <strong>Career Interest</strong>
        <div style="padding-top:6px;">{{ $D['comments']['career_interest'] ?? '' }}</div>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:8px; background:#fff;">
        <strong>Talent Noticed</strong>
        <div style="padding-top:6px;">{{ $D['comments']['talent_noticed'] ?? '' }}</div>
      </div>
    </td>
  </tr>
</table>

{{-- Signatures --}}
<table style="width:100%; border-collapse:separate; border-spacing:16px 0; margin-top:12px; margin-bottom:10px;">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="padding-top:24px; border-bottom:1px solid #374151; margin-bottom:6px;"></div>
      <div style="font-size:{{ !empty($isPdf) ? '9px' : '0.8rem' }}; color:#374151;">
        <strong>Class Teacher Signature</strong>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="padding-top:24px; border-bottom:1px solid #374151; margin-bottom:6px;"></div>
      <div style="font-size:{{ !empty($isPdf) ? '9px' : '0.8rem' }}; color:#374151;">
        <strong>Head Teacher Signature</strong>
      </div>
    </td>
  </tr>
</table>

{{-- Footer: branding + generation timestamp --}}
@php
  $footerSchool = $D['branding']['school_name'] ?? setting('school_name', config('app.name'));
  $footerPhone = $D['branding']['phone'] ?? setting('school_phone', '');
  $footerEmail = $D['branding']['email'] ?? setting('school_email', '');
  $footerWebsite = $D['branding']['website'] ?? setting('school_website', '');
  $footerGeneratedAt = $D['generated']['at'] ?? now()->format('d M Y, H:i');
  $footerGeneratedBy = $D['generated']['by'] ?? (auth()->user()?->name ?? 'System');
@endphp
<div style="margin-top:14px; padding-top:8px; border-top:1px solid #999; font-size:{{ !empty($isPdf) ? '8px' : '0.75rem' }}; color:#555;">
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="vertical-align:top;">
        <div>This report card is system-generated.</div>
        <div>Generated: {{ $footerGeneratedAt }} &middot; By: {{ $footerGeneratedBy }}</div>
      </td>
      <td style="vertical-align:top; text-align:right;">
        <div style="font-weight:600; color:#333;">{{ $footerSchool }}</div>
        @if($footerPhone !== '')<div>Tel: {{ $footerPhone }}</div>@endif
        @if($footerEmail !== '')<div>{{ $footerEmail }}</div>@endif
        @if($footerWebsite !== '')<div>{{ $footerWebsite }}</div>@endif
      </td>
    </tr>
  </table>
  @if(!empty($D['branding']['footer_html']))
    <div style="margin-top:6px;">{!! $D['branding']['footer_html'] !!}</div>
  @elseif(setting('pdf_footer_html'))
    <div style="margin-top:6px;">{!! setting('pdf_footer_html') !!}</div>
  @endif
</div>
