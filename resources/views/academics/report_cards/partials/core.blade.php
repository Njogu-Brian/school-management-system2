@php
  $D = $dto ?? [];
  $examHeaders = data_get(collect($D['subjects'] ?? [])->first(), 'exams', []);
  $referenceTermName = $D['context']['reference_term'] ?? null;
  $cbcLegend = collect(\App\Support\CbcGradePresentation::standardBands())
    ->map(fn ($band) => $band['short'].' = '.$band['label'])
    ->implode(' | ');
  $cellStyle = 'padding:'.(!empty($isPdf) ? '4px' : '6px').'; border:1px solid #d1d5db;';
  $smallFont = !empty($isPdf) ? '8px' : '0.78rem';
  $brandPrimary = setting('finance_primary_color', '#3a1a59');
  $brandSecondary = setting('finance_secondary_color', '#14b8a6');
  $chartMaxHeight = !empty($isPdf) ? 125 : 145;
  $chartAxisFont = !empty($isPdf) ? '9px' : '10px';
  $chartValueFont = !empty($isPdf) ? '9px' : '10px';
  $chartLabelFont = !empty($isPdf) ? '8px' : '9px';
  $chartBars = collect($D['year_trend']['points'] ?? [])->values()->map(function ($point, $index) use ($brandPrimary, $brandSecondary, $chartMaxHeight) {
    $value = max(0, min(100, (float) ($point['value'] ?? 0)));
    $height = max(8, (int) round(($value / 100) * $chartMaxHeight));

    return [
      'label' => $point['label'] ?? '',
      'value' => $value,
      'height' => $height,
      'color' => $index % 2 === 0 ? $brandPrimary : $brandSecondary,
    ];
  });
  $chartCount = max(1, $chartBars->count());
  $linePoints = $chartBars->values()->map(function ($bar, $index) use ($chartCount, $chartMaxHeight) {
    $slot = 100 / $chartCount;
    $xPercent = ($index * $slot) + ($slot / 2);

    return [
      'xPercent' => $xPercent,
      'y' => $chartMaxHeight - $bar['height'],
      'value' => $bar['value'],
    ];
  });
  $polylinePoints = $linePoints
    ->map(fn ($point) => round($point['xPercent'], 2).','.round($point['y'], 1))
    ->implode(' ');
  $overallPerformance = \App\Support\CbcGradePresentation::normalizeShortCode($D['cbc']['overall_performance_level'] ?? '') ?? null;
  $overallPerformanceName = $D['cbc']['overall_performance_level_name'] ?? null;
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
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:{{ !empty($isPdf) ? '6px' : '12px' }};">
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

{{-- Performance overview: trend | attendance | overall level --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:8px;">
  <tr style="background:#f9fafb;">
    <td colspan="3" style="padding:6px 8px; border-bottom:1px solid #d1d5db; font-size:{{ $smallFont }};">
      <strong>Performance Overview</strong>
      @if($referenceTermName)
        &nbsp;|&nbsp; {{ $referenceTermName }}: <strong>{{ data_get($D, 'overview.reference_term.average') !== null ? number_format((float) data_get($D, 'overview.reference_term.average'), 1) : '—' }}</strong> ({{ data_get($D, 'overview.reference_term.grade') ?? '—' }})
      @endif
      &nbsp;|&nbsp; {{ $D['overview']['current_term']['name'] ?? ($D['context']['term'] ?? 'Term') }}: <strong>{{ data_get($D, 'overview.current_term.average') !== null ? number_format((float) data_get($D, 'overview.current_term.average'), 1) : '—' }}</strong> ({{ data_get($D, 'overview.current_term.grade') ?? '—' }})
    </td>
  </tr>
  <tr>
    <td style="width:42%; vertical-align:top; padding:8px; border-right:1px solid #d1d5db;">
      <div style="font-weight:700; font-size:{{ $smallFont }}; margin-bottom:6px; color:{{ $brandPrimary }};">Academic Year Trend</div>
      @if($chartBars->count() > 0)
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="width:26px; vertical-align:bottom; font-size:{{ $chartAxisFont }}; color:#374151; line-height:1.15; padding:0 3px 0 0;">
              100<br>80<br>60<br>40<br>20<br>0
            </td>
            <td style="vertical-align:bottom; padding:0;">
              <div style="position:relative; height:{{ $chartMaxHeight }}px; border-left:1px solid #9ca3af; border-bottom:1px solid #9ca3af;">
                @foreach([20, 40, 60, 80] as $grid)
                  <div style="position:absolute; left:0; right:0; top:{{ $chartMaxHeight - (($grid / 100) * $chartMaxHeight) }}px; border-top:1px solid #edf2f7;"></div>
                @endforeach
                <table style="width:100%; height:{{ $chartMaxHeight }}px; border-collapse:collapse; position:relative; z-index:1;">
                  <tr style="vertical-align:bottom;">
                    @foreach($chartBars as $bar)
                      <td style="vertical-align:bottom; text-align:center; padding:0 2px;">
                        <div style="font-size:{{ $chartValueFont }}; font-weight:700; color:#111; margin-bottom:3px;">{{ number_format($bar['value'], 0) }}</div>
                        <div style="height:{{ $bar['height'] }}px; background:{{ $bar['color'] }}; width:72%; min-width:16px; max-width:42px; margin:0 auto;"></div>
                      </td>
                    @endforeach
                  </tr>
                </table>
                <svg style="position:absolute; left:0; top:0; width:100%; height:{{ $chartMaxHeight }}px; z-index:2; overflow:visible;" viewBox="0 0 100 {{ $chartMaxHeight }}" preserveAspectRatio="none">
                  <polyline fill="none" stroke="{{ $brandSecondary }}" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round" points="{{ $polylinePoints }}"></polyline>
                  @foreach($linePoints as $point)
                    <circle cx="{{ round($point['xPercent'], 2) }}" cy="{{ round($point['y'], 1) }}" r="1.6" fill="{{ $brandSecondary }}" stroke="#ffffff" stroke-width="0.4"></circle>
                  @endforeach
                </svg>
              </div>
              <table style="width:100%; border-collapse:collapse; margin-top:4px;">
                <tr>
                  @foreach($chartBars as $bar)
                    <td style="text-align:center; font-size:{{ $chartLabelFont }}; color:#374151; line-height:1.2; padding:0 1px; vertical-align:top;">{{ $bar['label'] }}</td>
                  @endforeach
                </tr>
              </table>
            </td>
          </tr>
        </table>
      @else
        <div style="font-size:{{ $smallFont }}; color:#6b7280;">No trend data yet.</div>
      @endif
    </td>
    <td style="width:29%; vertical-align:top; padding:8px; border-right:1px solid #d1d5db;">
      <div style="font-weight:700; font-size:{{ $smallFont }}; margin-bottom:6px; color:{{ $brandPrimary }};">Attendance &amp; Behaviour</div>
      <table style="width:100%; border-collapse:collapse; font-size:{{ $smallFont }};">
        <tr>
          <td style="padding:4px; border:1px solid #d1d5db;"><strong>Present</strong></td>
          <td style="padding:4px; border:1px solid #d1d5db;">{{ $D['attendance']['present'] ?? 0 }} / {{ $D['attendance']['total'] ?? ($D['attendance']['expected_school_days'] ?? 0) }}</td>
        </tr>
        <tr>
          <td style="padding:4px; border:1px solid #d1d5db;"><strong>Late</strong></td>
          <td style="padding:4px; border:1px solid #d1d5db;">{{ $D['attendance']['late'] ?? 0 }}</td>
        </tr>
        <tr>
          <td style="padding:4px; border:1px solid #d1d5db;"><strong>Absent</strong></td>
          <td style="padding:4px; border:1px solid #d1d5db;">{{ $D['attendance']['absent'] ?? 0 }}</td>
        </tr>
        <tr>
          <td style="padding:4px; border:1px solid #d1d5db;"><strong>Attendance %</strong></td>
          <td style="padding:4px; border:1px solid #d1d5db;">{{ $D['attendance']['percent'] ?? 0 }}%</td>
        </tr>
        <tr>
          <td style="padding:4px; border:1px solid #d1d5db;"><strong>Behaviour</strong></td>
          <td style="padding:4px; border:1px solid #d1d5db;">
            Total {{ $D['behavior']['count'] ?? 0 }} &middot; +ve {{ $D['behavior']['positive'] ?? 0 }} &middot; -ve {{ $D['behavior']['negative'] ?? 0 }}
          </td>
        </tr>
      </table>
      @if(!empty($D['behavior']['latest']))
        <div style="font-size:{{ $chartLabelFont }}; margin-top:4px; color:#374151;">
          @foreach(array_slice($D['behavior']['latest'], 0, 2) as $b)
            <div>{{ $b['date'] }}: {{ $b['name'] }}</div>
          @endforeach
        </div>
      @endif
    </td>
    <td style="width:29%; vertical-align:top; padding:8px;">
      <div style="font-weight:700; font-size:{{ $smallFont }}; margin-bottom:6px; color:{{ $brandPrimary }};">Overall Performance Level</div>
      @if($overallPerformance)
        <div style="border:1px solid #d1d5db; background:#f9fafb; padding:10px; text-align:center; margin-bottom:6px;">
          <div style="font-size:{{ !empty($isPdf) ? '18px' : '1.4rem' }}; font-weight:700; color:{{ $brandPrimary }};">{{ $overallPerformance }}</div>
          @if($overallPerformanceName)
            <div style="font-size:{{ $smallFont }}; color:#374151; margin-top:4px;">{{ $overallPerformanceName }}</div>
          @endif
        </div>
        <div style="font-size:{{ $chartLabelFont }}; color:#555; line-height:1.35;">{{ $cbcLegend }}</div>
      @else
        <div style="font-size:{{ $smallFont }}; color:#6b7280;">No overall performance level recorded.</div>
      @endif
    </td>
  </tr>
</table>

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
@php $commentPad = !empty($isPdf) ? '6px' : '8px'; @endphp
<table style="width:100%; border-collapse:separate; border-spacing:{{ !empty($isPdf) ? '6px' : '10px' }} 0; margin-top:{{ !empty($isPdf) ? '6px' : '10px' }};">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:{{ $commentPad }}; background:#fff; font-size:{{ $smallFont }};">
        <strong>Class Teacher’s Remark</strong>
        <div style="padding-top:4px;">{{ $D['comments']['teacher_remark'] ?? '' }}</div>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:{{ $commentPad }}; background:#fff; font-size:{{ $smallFont }};">
        <strong>Head Teacher’s Remark</strong>
        <div style="padding-top:4px;">{{ $D['comments']['headteacher_remark'] ?? '' }}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:{{ $commentPad }}; background:#fff; font-size:{{ $smallFont }};">
        <strong>Career Interest</strong>
        <div style="padding-top:4px;">{{ $D['comments']['career_interest'] ?? '' }}</div>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #d1d5db; padding:{{ $commentPad }}; background:#fff; font-size:{{ $smallFont }};">
        <strong>Talent Noticed</strong>
        <div style="padding-top:4px;">{{ $D['comments']['talent_noticed'] ?? '' }}</div>
      </div>
    </td>
  </tr>
</table>

{{-- Signatures --}}
<table style="width:100%; border-collapse:separate; border-spacing:{{ !empty($isPdf) ? '10px' : '16px' }} 0; margin-top:{{ !empty($isPdf) ? '8px' : '12px' }}; margin-bottom:{{ !empty($isPdf) ? '6px' : '10px' }};">
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
