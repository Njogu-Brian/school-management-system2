@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
<style>
  @media print {
    .no-print, .sidebar, .topbar, .page-header .btn, nav, .crumb { display: none !important; }
    .settings-page, .settings-shell { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
    .settings-card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ccc !important; }
    body { background: #fff !important; }
  }
  .sheet-meta { font-size: 0.9rem; color: #555; }
  .sheet-table th, .sheet-table td { font-size: 0.9rem; vertical-align: middle; }
</style>
@endpush

@section('content')
@php
  $sheetDate = \Carbon\Carbon::parse($date);
  $driverName = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''))
    ?: ($staff->user->name ?? 'Driver');
@endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 no-print">
            <div>
                <div class="crumb"><a href="{{ route('driver.index') }}">Driver</a> / Transport Sheet</div>
                <h1>Transport Sheet</h1>
                <p class="mb-0">Printable student list for your assigned trips.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <form method="GET" action="{{ route('driver.transport-sheet') }}" class="d-flex gap-2 flex-wrap">
                    <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
                    <select name="type" class="form-select" onchange="this.form.submit()" style="min-width: 120px;">
                        <option value="daily" @selected(($type ?? 'daily') === 'daily')>Daily</option>
                        <option value="weekly" @selected(($type ?? '') === 'weekly')>Weekly</option>
                    </select>
                </form>
                <button type="button" class="btn btn-settings-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('driver.index', ['date' => $date]) }}" class="btn btn-ghost-strong">Back</a>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-bold fs-5">{{ config('app.name', 'School ERP') }} — Transport Sheet</div>
                        <div class="sheet-meta">Driver: <strong>{{ $driverName }}</strong></div>
                    </div>
                    <div class="text-md-end sheet-meta">
                        <div>Date: <strong>{{ $sheetDate->format('l, F j, Y') }}</strong></div>
                        <div>Sheet: {{ ucfirst($type ?? 'daily') }}</div>
                        <div>Printed: {{ now()->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        @forelse($transportData as $block)
            @php
                $trip = $block['trip'];
                $students = $block['students'];
            @endphp
            <div class="settings-card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">{{ $trip->trip_name ?? $trip->name }}</h5>
                        <small class="text-muted">
                            @if($trip->type){{ $trip->type }} · @endif
                            @if($trip->direction){{ ucfirst($trip->direction) }} · @endif
                            Vehicle: {{ $trip->vehicle->vehicle_number ?? 'N/A' }}
                            · {{ $students->count() }} student(s)
                        </small>
                    </div>
                    <a href="{{ route('driver.trips.show', $trip) }}?date={{ $date }}" class="btn btn-sm btn-ghost-strong no-print">
                        Open trip
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($students->isEmpty())
                        <div class="text-center text-muted py-4">No students assigned to this trip for this date.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-modern sheet-table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Student</th>
                                        <th>Admission</th>
                                        <th>Class</th>
                                        <th>Stream</th>
                                        <th>Drop-off / notes</th>
                                        <th style="width:70px;">✓</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $student)
                                        @php
                                            $assignment = method_exists($student, 'assignments')
                                                ? $student->assignments->first()
                                                : null;
                                            $isEvening = ($trip->direction === 'dropoff') || ($trip->type === 'Evening');
                                            $dropName = $isEvening
                                                ? (optional($assignment?->eveningDropOffPoint)->name
                                                    ?? optional($student->dropOffPoint)->name
                                                    ?? $student->drop_off_point_other)
                                                : (optional($assignment?->morningDropOffPoint)->name
                                                    ?? optional($student->dropOffPoint)->name
                                                    ?? $student->drop_off_point_other);
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-semibold">{{ $student->full_name }}</td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ optional($student->classroom)->name ?? '—' }}</td>
                                            <td>{{ optional($student->stream)->name ?? '—' }}</td>
                                            <td>{{ $dropName ?: '—' }}</td>
                                            <td>☐</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="settings-card mt-3">
                <div class="card-body text-center text-muted py-5">
                    No trips assigned to you for {{ $sheetDate->format('F j, Y') }}.
                </div>
            </div>
        @endforelse

        @if(($type ?? 'daily') === 'weekly')
            <div class="settings-card mt-3 no-print">
                <div class="card-body small text-muted">
                    Weekly view currently shows the selected day’s trips. Change the date to print other weekdays.
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
