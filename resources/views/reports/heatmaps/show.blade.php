@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Campus & Weekly Reports</div>
        <h1 class="mb-1">{{ strtoupper($campus) }} Campus Heatmap</h1>
        <p class="text-muted mb-0">Class averages by subject (week ending filter).</p>
      </div>
      <form method="GET" class="d-flex gap-2 align-items-center">
        <input type="date" name="week_ending" value="{{ $weekEnding }}" class="form-control" />
        <button type="submit" class="btn btn-ghost-strong">Filter</button>
      </form>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-thermometer-half"></i> Class vs Subject Averages</h5>
        <p class="text-muted small mb-0">Green ≥80%, Yellow ≥60%, Red &lt;60%.</p>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class</th>
                @foreach ($subjects as $subject)
                  <th class="text-nowrap">{{ $subject->name }}</th>
                @endforeach
                <th class="text-nowrap">Class Avg %</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($classrooms as $classroom)
                @php
                  $rows = $averages->get($classroom->id, collect());
                  $subjectAverages = $rows->keyBy('subject_id');
                  $classAvg = $rows->avg('avg_percent');
                @endphp
                <tr>
                  <td class="text-nowrap fw-semibold">{{ $classroom->name }}</td>
                  @foreach ($subjects as $subject)
                    @php
                      $avg = optional($subjectAverages->get($subject->id))->avg_percent;
                      $color = $avg === null ? '#f8f9fa' : ($avg >= 80 ? '#d4edda' : ($avg >= 60 ? '#fff3cd' : '#f8d7da'));
                    @endphp
                    <td style="background-color: {{ $color }}; text-align:center;">
                      {{ $avg !== null ? number_format($avg, 1) : '–' }}
                    </td>
                  @endforeach
                  <td class="text-center fw-bold">
                    {{ $classAvg !== null ? number_format($classAvg, 1) : '–' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $subjects->count() + 2 }}" class="text-center text-muted p-4">
                    No classes found for this campus.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
