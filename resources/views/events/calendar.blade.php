@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Events / Calendar</div>
                <h1>Events Calendar</h1>
                <p>See everything happening across the school in one view.</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="settings-chip"><i class="bi bi-calendar4-week"></i> Month / Week / Day</span>
                    <span class="settings-chip"><i class="bi bi-broadcast"></i> Live updates</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                    <a href="{{ route('events.create') }}" class="btn btn-settings-primary">
                        <i class="bi bi-plus-circle"></i> Add Event
                    </a>
                @endif
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Academic Year</label>
                        <select name="year" class="form-select">
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" {{ ($year && $year->id == $y->id) ? 'selected' : '' }}>
                                    {{ $y->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Calendar</h5>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: '{{ route("events.api") }}',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            }
        });
        calendar.render();
    });
</script>
@endsection