@php($D = $dto ?? [])
@php($examHeaders = collect($D['subjects'])->first()['exams'] ?? [])

{{-- Header --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8px; {{ $isPdf ? '' : 'border:0' }}">
  <tr>
    <td style="border:0;">
      <div style="font-weight:700; font-size:18px;">
        {{ $D['branding']['school_name'] ?? ($D['context']['school']['name'] ?? config('app.name')) }}
      </div>
      <div style="font-size:11px; color:#666;">
        {{ $D['branding']['address'] ?? '' }}
        @if(!empty($D['branding']['phone'])) | {{ $D['branding']['phone'] }} @endif
      </div>
    </td>
    <td style="border:0; text-align:right;">
      <div style="font-weight:700;">Academic Report</div>
      <div style="font-size:11px; color:#666;">Printed: {{ $D['generated']['at'] ?? now()->format('d M Y H:i') }}</div>
    </td>
  </tr>
</table>

{{-- Student & term --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:10px;">
  <tr>
    <td style="padding:6px;"><strong>Student:</strong> {{ $D['student']['name'] ?? '' }}</td>
    <td style="padding:6px;"><strong>Adm No:</strong> {{ $D['student']['admission_number'] ?? '' }}</td>
    <td style="padding:6px;"><strong>Class:</strong> {{ $D['student']['class'] ?? '' }} {{ !empty($D['student']['stream']) ? '— '.$D['student']['stream'] : '' }}</td>
    <td style="padding:6px;"><strong>Term/Year:</strong> {{ $D['context']['term'] ?? '' }} / {{ $D['context']['year'] ?? '' }}</td>
  </tr>
</table>

{{-- Subjects --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:12px;">
  <thead>
    <tr style="background:#f3f3f3;">
      <th style="padding:6px; border:1px solid #444; text-align:left;">Subject</th>
      @foreach($examHeaders as $eh)
        <th style="padding:6px; border:1px solid #444; text-align:left;">{{ $eh['exam_name'] }}</th>
      @endforeach
      <th style="padding:6px; border:1px solid #444; text-align:left;">Term Avg</th>
      <th style="padding:6px; border:1px solid #444; text-align:left;">Grade</th>
      <th style="padding:6px; border:1px solid #444; text-align:left;">Teacher Remark</th>
    </tr>
  </thead>
  <tbody>
    @forelse($D['subjects'] as $row)
      <tr>
        <td style="padding:6px; border:1px solid #444;">{{ $row['subject_name'] }}</td>
        @foreach($row['exams'] as $ex)
          <td style="padding:6px; border:1px solid #444; text-align:center;">
            {{ $ex['score'] !== null ? number_format($ex['score'],2) : '—' }}
          </td>
        @endforeach
        <td style="padding:6px; border:1px solid #444; text-align:center;"><strong>{{ $row['term_avg'] !== null ? number_format($row['term_avg'],2) : '—' }}</strong></td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">{{ $row['grade_label'] ?? '' }}</td>
        <td style="padding:6px; border:1px solid #444;">{{ $row['teacher_remark'] ?? '' }}</td>
      </tr>
    @empty
      <tr><td colspan="{{ 3 + count($examHeaders) }}" style="padding:8px; text-align:center;">No subject marks.</td></tr>
    @endforelse
  </tbody>
</table>

{{-- Two-column: Skills / Attendance+Behaviour --}}
<table style="width:100%; border-collapse:separate; border-spacing:10px 0;">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Skills</strong>
        <table style="width:100%; margin-top:6px; border-collapse:collapse; border:1px solid #444;">
          <thead>
            <tr style="background:#f3f3f3;">
              <th style="padding:6px; border:1px solid #444; text-align:left;">Skill</th>
              <th style="padding:6px; border:1px solid #444; text-align:center;">Grade</th>
              <th style="padding:6px; border:1px solid #444; text-align:left;">Comment</th>
            </tr>
          </thead>
          <tbody>
            @forelse($D['skills'] as $s)
              <tr>
                <td style="padding:6px; border:1px solid #444;">{{ $s['skill'] }}</td>
                <td style="padding:6px; border:1px solid #444; text-align:center;">{{ $s['grade'] }}</td>
                <td style="padding:6px; border:1px solid #444;">{{ $s['comment'] }}</td>
              </tr>
            @empty
              <tr><td colspan="3" style="padding:8px; text-align:center;">No skills graded.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </td>

    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Attendance & Behaviour</strong>
        <table style="width:100%; margin-top:6px; border-collapse:collapse; border:1px solid #444;">
          <tr>
            <td style="padding:6px; border:1px solid #444; width:40%;"><strong>Attendance</strong></td>
            <td style="padding:6px; border:1px solid #444;">Present: {{ $D['attendance']['present'] ?? 0 }}</td>
            <td style="padding:6px; border:1px solid #444;">Late: {{ $D['attendance']['late'] ?? 0 }}</td>
            <td style="padding:6px; border:1px solid #444;">Absent: {{ $D['attendance']['absent'] ?? 0 }}</td>
            <td style="padding:6px; border:1px solid #444;">%: {{ $D['attendance']['percent'] ?? 0 }}</td>
          </tr>
          <tr>
            <td style="padding:6px; border:1px solid #444;"><strong>Behaviour</strong></td>
            <td colspan="4" style="padding:6px; border:1px solid #444;">
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
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-top:10px; margin-bottom:10px;">
  <tr style="background:#f3f3f3;">
    <th style="padding:6px; border:1px solid #444; text-align:left;">Overall Performance Level</th>
    <td style="padding:6px; border:1px solid #444;">
      <strong>{{ $D['cbc']['overall_performance_level'] ?? 'N/A' }}</strong> - 
      {{ $D['cbc']['overall_performance_level_name'] ?? 'N/A' }}
    </td>
  </tr>
</table>
@endif

{{-- CBC Core Competencies --}}
@if(!empty($D['cbc']['core_competencies']) && is_array($D['cbc']['core_competencies']) && count($D['cbc']['core_competencies']) > 0)
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:10px;">
  <thead>
    <tr style="background:#f3f3f3;">
      <th style="padding:6px; border:1px solid #444; text-align:left;" colspan="3">Core Competencies</th>
    </tr>
    <tr style="background:#f9f9f9;">
      <th style="padding:6px; border:1px solid #444; text-align:left;">Competency</th>
      <th style="padding:6px; border:1px solid #444; text-align:center;">Code</th>
      <th style="padding:6px; border:1px solid #444; text-align:center;">Average Score</th>
    </tr>
  </thead>
  <tbody>
    @foreach($D['cbc']['core_competencies'] as $code => $competency)
      <tr>
        <td style="padding:6px; border:1px solid #444;">{{ $competency['name'] ?? $code }}</td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">{{ $code }}</td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $competency['average'] !== null ? number_format($competency['average'], 2) : 'N/A' }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- CBC Learning Areas Performance --}}
@if(!empty($D['cbc']['learning_areas_performance']) && is_array($D['cbc']['learning_areas_performance']) && count($D['cbc']['learning_areas_performance']) > 0)
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:10px;">
  <thead>
    <tr style="background:#f3f3f3;">
      <th style="padding:6px; border:1px solid #444; text-align:left;" colspan="4">Learning Areas Performance</th>
    </tr>
    <tr style="background:#f9f9f9;">
      <th style="padding:6px; border:1px solid #444; text-align:left;">Learning Area</th>
      <th style="padding:6px; border:1px solid #444; text-align:center;">Average (%)</th>
      <th style="padding:6px; border:1px solid #444; text-align:center;">Performance Level</th>
      <th style="padding:6px; border:1px solid #444; text-align:center;">Subjects</th>
    </tr>
  </thead>
  <tbody>
    @foreach($D['cbc']['learning_areas_performance'] as $area => $performance)
      <tr>
        <td style="padding:6px; border:1px solid #444;">{{ $area }}</td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $performance['average'] !== null ? number_format($performance['average'], 2) : 'N/A' }}%
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          <strong>{{ $performance['performance_level'] ?? 'N/A' }}</strong>
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $performance['subjects_count'] ?? 0 }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- CBC CAT Breakdown --}}
@if(!empty($D['cbc']['cat_breakdown']) && is_array($D['cbc']['cat_breakdown']))
  @php
    $hasCatData = false;
    foreach($D['cbc']['cat_breakdown'] as $key => $value) {
      if ($key !== 'average' && $value !== null) {
        $hasCatData = true;
        break;
      }
    }
  @endphp
  @if($hasCatData)
  <table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:10px;">
    <thead>
      <tr style="background:#f3f3f3;">
        <th style="padding:6px; border:1px solid #444; text-align:left;" colspan="5">CAT Breakdown</th>
      </tr>
      <tr style="background:#f9f9f9;">
        <th style="padding:6px; border:1px solid #444; text-align:center;">CAT 1</th>
        <th style="padding:6px; border:1px solid #444; text-align:center;">CAT 2</th>
        <th style="padding:6px; border:1px solid #444; text-align:center;">CAT 3</th>
        <th style="padding:6px; border:1px solid #444; text-align:center;">Average</th>
        <th style="padding:6px; border:1px solid #444; text-align:left;">Performance Level</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $D['cbc']['cat_breakdown']['cat_1']['percentage'] ?? 'N/A' }}%
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $D['cbc']['cat_breakdown']['cat_2']['percentage'] ?? 'N/A' }}%
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $D['cbc']['cat_breakdown']['cat_3']['percentage'] ?? 'N/A' }}%
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          <strong>{{ $D['cbc']['cat_breakdown']['average'] !== null ? number_format($D['cbc']['cat_breakdown']['average'], 2) . '%' : 'N/A' }}</strong>
        </td>
        <td style="padding:6px; border:1px solid #444; text-align:center;">
          {{ $D['cbc']['cat_breakdown']['cat_1']['performance_level'] ?? $D['cbc']['cat_breakdown']['cat_2']['performance_level'] ?? $D['cbc']['cat_breakdown']['cat_3']['performance_level'] ?? 'N/A' }}
        </td>
      </tr>
    </tbody>
  </table>
  @endif
@endif

{{-- CBC Portfolio Summary --}}
@if(!empty($D['cbc']['portfolio_summary']) && is_array($D['cbc']['portfolio_summary']) && !empty($D['cbc']['portfolio_summary']['total']))
<table style="width:100%; border-collapse:collapse; border:1px solid #444; margin-bottom:10px;">
  <tr style="background:#f3f3f3;">
    <th style="padding:6px; border:1px solid #444; text-align:left;">Portfolio Summary</th>
    <td style="padding:6px; border:1px solid #444;">
      Total: {{ $D['cbc']['portfolio_summary']['total'] ?? 0 }}, 
      Average Score: {{ $D['cbc']['portfolio_summary']['average_score'] !== null ? number_format($D['cbc']['portfolio_summary']['average_score'], 2) : 'N/A' }}
    </td>
  </tr>
</table>
@endif

{{-- Remarks --}}
<table style="width:100%; border-collapse:separate; border-spacing:10px 0; margin-top:10px;">
  <tr>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Class Teacher's Remark</strong>
        <div style="padding-top:8px;">{{ $D['comments']['teacher_remark'] ?? '' }}</div>
      </div>
    </td>
    <td style="width:50%; vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Head Teacher's Remark</strong>
        <div style="padding-top:8px;">{{ $D['comments']['headteacher_remark'] ?? '' }}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style="vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Career Interest</strong>
        <div style="padding-top:8px;">{{ $D['comments']['career_interest'] ?? '' }}</div>
      </div>
    </td>
    <td style="vertical-align:top;">
      <div style="border:1px solid #999; padding:8px;">
        <strong>Talent Noticed</strong>
        <div style="padding-top:8px;">{{ $D['comments']['talent_noticed'] ?? '' }}</div>
      </div>
    </td>
  </tr>
</table>

{{-- Footer --}}
<div style="font-size:11px; color:#666; margin-top:12px;">
  Generated by {{ $D['generated']['by'] ?? (auth()->user()->name ?? 'System') }}
  on {{ $D['generated']['at'] ?? now()->format('d M Y H:i') }}
</div>
