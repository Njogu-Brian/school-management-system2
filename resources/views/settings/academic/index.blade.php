@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Academic Calendar</div>
                <h1>Academic Years & Terms</h1>
                <p>Keep the school calendar aligned for attendance, billing, and reporting.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('settings.academic.year.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-calendar-plus"></i> Add Academic Year
                </a>
                <a href="{{ route('settings.academic.term.create') }}" class="btn btn-ghost">
                    <i class="bi bi-plus-circle"></i> Add Term
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Calendar Overview</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Active</th>
                                <th>Terms</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($years as $year)
                                <tr>
                                    <td class="fw-semibold">{{ $year->year }}</td>
                                    <td>
                                        <span class="pill-badge {{ $year->is_active ? '' : 'bg-light text-dark border' }}">
                                            {{ $year->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row g-2">
                                            @foreach($year->terms as $term)
                                                <div class="col-12">
                                                    <div class="settings-card mb-2">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                                                        {{ $term->name }}
                                                                        @if($term->is_current)
                                                                            <span class="pill-badge bg-success border-0 text-white">Current</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="form-note mt-1">
                                                                        @if($term->opening_date && $term->closing_date)
                                                                            <div><i class="bi bi-calendar-event"></i> Opens: {{ $term->opening_date->format('M d, Y') }}</div>
                                                                            <div><i class="bi bi-calendar-x"></i> Closes: {{ $term->closing_date->format('M d, Y') }}</div>
                                                                            @if($term->midterm_start_date && $term->midterm_end_date)
                                                                                <div><i class="bi bi-calendar-range"></i> Midterm: {{ $term->midterm_start_date->format('M d') }} - {{ $term->midterm_end_date->format('M d, Y') }}</div>
                                                                            @endif
                                                                            @php
                                                                                $holidays = \App\Models\SchoolDay::whereBetween('date', [$term->opening_date, $term->closing_date])
                                                                                    ->where('type', \App\Models\SchoolDay::TYPE_HOLIDAY)
                                                                                    ->orderBy('date')
                                                                                    ->limit(3)
                                                                                    ->get();
                                                                                $holidayCount = \App\Models\SchoolDay::whereBetween('date', [$term->opening_date, $term->closing_date])
                                                                                    ->where('type', \App\Models\SchoolDay::TYPE_HOLIDAY)
                                                                                    ->count();

                                                                                // Breaks between this term and the next one (in this academic year)
                                                                                $nextTerm = $year->terms->first(function($t) use ($term) {
                                                                                    return $t->opening_date && $term->closing_date && $t->opening_date->gt($term->closing_date);
                                                                                });
                                                                                $breaks = collect();
                                                                                if ($nextTerm && $term->closing_date) {
                                                                                    $gapStart = $term->closing_date->copy()->addDay();
                                                                                    $gapEnd = $nextTerm->opening_date->copy()->subDay();
                                                                                    if ($gapStart->lte($gapEnd)) {
                                                                                        $breaks->push([
                                                                                            'start' => $gapStart,
                                                                                            'end' => $gapEnd,
                                                                                        ]);
                                                                                    }
                                                                                }
                                                                            @endphp
                                                                            @if($holidayCount > 0)
                                                                                <div class="mt-2">
                                                                                    <div class="fw-semibold small"><i class="bi bi-sun"></i> School & Public Holidays ({{ $holidayCount }})</div>
                                                                                    @foreach($holidays as $holiday)
                                                                                        <div class="text-muted small">• {{ $holiday->date->format('M d, Y') }} — {{ $holiday->name ?? 'Holiday' }}</div>
                                                                                    @endforeach
                                                                                    @if($holidayCount > $holidays->count())
                                                                                        <div class="text-muted small">… and {{ $holidayCount - $holidays->count() }} more</div>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                            @if($breaks->isNotEmpty())
                                                                                <div class="mt-2">
                                                                                    <div class="fw-semibold small"><i class="bi bi-beach"></i> Inter-term Break</div>
                                                                                    @foreach($breaks as $break)
                                                                                        <div class="text-muted small">• {{ $break['start']->format('M d, Y') }} – {{ $break['end']->format('M d, Y') }}</div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-warning">Dates not set</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex gap-2">
                                                                    <a href="{{ route('settings.academic.term.edit', $term) }}" class="btn btn-sm btn-ghost">Edit</a>
                                                                    <form action="{{ route('settings.academic.term.destroy', $term) }}" method="POST" onsubmit="return confirm('Delete term?')">
                                                                        @csrf @method('DELETE')
                                                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('settings.academic.year.edit', $year) }}" class="btn btn-sm btn-ghost">Edit</a>
                                        <form action="{{ route('settings.academic.year.destroy', $year) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete year?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $years->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
