@php
  $block = $mostImproved ?? ($payload['most_improved'] ?? null);
  $rows = collect($block['rows'] ?? []);
  $comparisonLabel = $block['comparison_label'] ?? null;
  $showStreamColumn = $showStreamColumn ?? false;
@endphp

@if($block)
  <div class="most-improved-panel border-top px-3 py-3">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
      <h6 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-graph-up-arrow text-success"></i>
        Most Improved
      </h6>
      @if($comparisonLabel)
        <span class="badge text-bg-light border">{{ $comparisonLabel }}</span>
      @endif
      <span class="text-muted small">By overall total marks vs previous exam in this term</span>
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
                @if($comparisonLabel)
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
