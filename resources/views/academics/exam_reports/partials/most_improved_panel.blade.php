@php
  $block = $mostImproved ?? ($payload['most_improved'] ?? null);
  // Legacy payloads stored only the ranked rows collection.
  if ($block instanceof \Illuminate\Support\Collection || (is_array($block) && array_is_list($block))) {
      $block = [
          'rows' => collect($block),
          'comparison_label' => $payload['meta']['comparison_label'] ?? null,
          'previous' => null,
          'current' => null,
          'available_sittings' => [],
      ];
  }
  $rows = collect($block['rows'] ?? []);
  $comparisonLabel = $block['comparison_label'] ?? null;
  $showStreamColumn = $showStreamColumn ?? false;
  $availableSittings = collect($block['available_sittings'] ?? []);
  $selectedSittingId = (int) ($block['current']['exam_type_id'] ?? request('improvement_exam_type_id') ?? 0);
  $allowSittingPicker = $allowSittingPicker ?? in_array(
      request('sheet_flow', request('analysis_flow', request('mode'))),
      ['term'],
      true
  );
  $showSittingPicker = $allowSittingPicker && $availableSittings->count() > 1;
  $withBorder = $withBorder ?? true;
@endphp

@if($block)
  <div class="most-improved-panel {{ $withBorder ? 'border-top' : '' }} px-3 py-3">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
      <h6 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-graph-up-arrow text-success"></i>
        Improvement Scale
      </h6>
      @if($comparisonLabel)
        <span class="badge text-bg-light border">{{ $comparisonLabel }}</span>
      @endif
      @if($showSittingPicker)
        <form method="GET" class="d-inline-flex align-items-center gap-2 ms-auto no-print mb-0">
          @foreach(request()->except('improvement_exam_type_id') as $key => $value)
            @if(is_array($value))
              @foreach($value as $nested)
                <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
              @endforeach
            @else
              <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
          @endforeach
          @unless(request()->filled('load'))
            <input type="hidden" name="load" value="1">
          @endunless
          <label class="form-label small mb-0 text-nowrap" for="improvement_exam_type_id">Compare exam</label>
          <select name="improvement_exam_type_id" id="improvement_exam_type_id" class="form-select form-select-sm" style="min-width:9rem;" onchange="this.form.submit()">
            @foreach($availableSittings as $sitting)
              <option value="{{ $sitting['exam_type_id'] }}" @selected($selectedSittingId === (int) $sitting['exam_type_id'])>
                {{ $sitting['exam_type'] }}
              </option>
            @endforeach
          </select>
        </form>
      @endif
      <span class="text-muted small {{ $showSittingPicker ? 'w-100' : '' }}">All students ranked by change in overall total marks vs previous exam in this term (KNEC CBA excluded)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-modern table-sm align-middle mb-0 exam-report-marks-table">
        <thead class="table-light">
          <tr>
            <th style="width:3rem;">#</th>
            <th>Adm No</th>
            <th>Student</th>
            @if($showStreamColumn)
              <th>Stream</th>
            @endif
            <th class="text-end">{{ $block['previous']['exam_type'] ?? 'Previous' }}</th>
            <th class="text-end">{{ $block['current']['exam_type'] ?? 'Current' }}</th>
            <th class="text-end">Change</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $row)
            <tr>
              <td class="fw-semibold">{{ $i + 1 }}</td>
              <td>{{ $row['admission_number'] }}</td>
              <td class="fw-semibold">{{ $row['name'] }}</td>
              @if($showStreamColumn)
                <td>{{ $row['stream_name'] ?? '—' }}</td>
              @endif
              <td class="text-end">{{ $row['prev_total'] }}</td>
              <td class="text-end">{{ $row['curr_total'] }}</td>
              <td class="text-end fw-semibold {{ ($row['improvement'] ?? 0) > 0 ? 'text-success' : (($row['improvement'] ?? 0) < 0 ? 'text-danger' : '') }}">
                {{ ($row['improvement'] ?? 0) > 0 ? '+' : '' }}{{ $row['improvement'] }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $showStreamColumn ? 7 : 6 }}" class="text-center text-muted py-3">
                @if(!empty($block['excluded']))
                  KNEC CBA is excluded from improvement analysis. Choose Mid Term or End Term.
                @elseif($comparisonLabel)
                  No students with marks in both sittings to compare.
                @else
                  No previous exam sitting in this term (e.g. viewing Opener — compare from Mid Term or End Term).
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
