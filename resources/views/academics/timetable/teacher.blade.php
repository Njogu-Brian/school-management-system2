@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable</div>
        <h1 class="mb-1">My Timetable</h1>
        <p class="text-muted mb-0">{{ $teacher->full_name }}</p>
      </div>
      <a href="{{ route('academics.timetable.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @if(isset($timetable) && isset($timetable['schedule']) && count($timetable['schedule']) > 0)
      @php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = [
            ['start' => '08:00', 'end' => '08:40', 'period' => 1],
            ['start' => '08:40', 'end' => '09:20', 'period' => 2],
            ['start' => '09:20', 'end' => '10:00', 'period' => 3],
            ['start' => '10:00', 'end' => '10:20', 'period' => 'Break'],
            ['start' => '10:20', 'end' => '11:00', 'period' => 4],
            ['start' => '11:00', 'end' => '11:40', 'period' => 5],
            ['start' => '11:40', 'end' => '12:20', 'period' => 6],
            ['start' => '12:20', 'end' => '13:00', 'period' => 'Lunch'],
            ['start' => '13:00', 'end' => '13:40', 'period' => 7],
            ['start' => '13:40', 'end' => '14:20', 'period' => 8],
        ];
        $scheduleMap = [];
        foreach ($timetable['schedule'] as $item) {
            $scheduleMap[$item['day']][$item['period']] = $item;
        }
      @endphp
      <div class="settings-card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Time</th>
                  @foreach($days as $day)<th>{{ $day }}</th>@endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($timeSlots as $slot)
                  <tr>
                    <td class="fw-semibold">
                      @if(in_array($slot['period'], ['Break', 'Lunch']))
                        {{ $slot['period'] }}<br><small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                      @else
                        Period {{ $slot['period'] }}<br><small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                      @endif
                    </td>
                    @foreach($days as $day)
                      <td>
                        @if(isset($scheduleMap[$day][$slot['period']]))
                          @php $item = $scheduleMap[$day][$slot['period']]; @endphp
                          @if(in_array($slot['period'], ['Break', 'Lunch']))
                            <span class="text-muted">{{ $slot['period'] }}</span>
                          @else
                            <div class="p-2 bg-light rounded">
                              <strong>{{ $item['subject']->name ?? 'N/A' }}</strong><br>
                              <small>{{ $item['classroom']->name ?? 'N/A' }}</small>
                              @if(isset($item['start']) && isset($item['end']))
                                <br><small class="text-muted">{{ $item['start'] }} - {{ $item['end'] }}</small>
                              @endif
                            </div>
                          @endif
                        @else
                          <span class="text-muted">Free</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @else
      <div class="alert alert-info alert-soft border-0"><i class="bi bi-info-circle"></i> No timetable found for this teacher. Please generate a timetable first.</div>
    @endif
  </div>
</div>
@endsection
