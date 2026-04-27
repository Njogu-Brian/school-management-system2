@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div class="crumb">Teacher / Fee Clearance</div>
            <h2 class="mb-1">Fee Clearance Status</h2>
            <div class="text-muted">
                @if($term)
                    Current term: <strong>{{ $term->name }}</strong>
                @else
                    <strong>No current term set</strong> (ask admin to set a current term)
                @endif
            </div>
        </div>
    </div>

    @if($term && ($term->fee_clearance_day1_date || $term->fee_clearance_strict_from_date || $term->opening_date))
        @php
            $day1 = ($term->fee_clearance_day1_date ?: $term->opening_date);
            $strictFrom = ($term->fee_clearance_strict_from_date ?: ($day1 ? $day1->copy()->addDay() : null));
        @endphp
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Enforcement</div>
            <div class="small">
                Day 1: {{ $day1 ? $day1->format('M d, Y') : '—' }}.
                Strict enforcement from: {{ $strictFrom ? $strictFrom->format('M d, Y') : '—' }}.
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Class</label>
                    <select name="classroom_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Select class</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ (int)$selectedClassId === (int)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stream (optional)</label>
                    <input type="number" name="stream_id" class="form-control" value="{{ $selectedStreamId }}" placeholder="Stream ID">
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
            <div class="form-text mt-2">
                Teachers only see <strong>Cleared</strong> or <strong>Pending</strong> (no amounts).
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Students</div>
            <div class="text-muted small">{{ $students->count() }} total</div>
        </div>
        <div class="card-body p-0">
            @if(!$selectedClassId)
                <div class="p-4 text-muted">Select a class to view clearance status.</div>
            @elseif($students->isEmpty())
                <div class="p-4 text-muted">No students found for the selected filters.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Adm #</th>
                                <th>Status</th>
                                <th class="text-end">Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $st)
                                @php
                                    $snap = $snapshots->get($st->id);
                                    $status = $snap?->status ?? 'pending';
                                    $deadline = $snap?->displayFinalClearanceDeadline();
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $st->full_name ?? ($st->first_name.' '.$st->last_name) }}</td>
                                    <td>{{ $st->admission_number }}</td>
                                    <td>
                                        @if($status === 'cleared')
                                            <span class="badge bg-success">Cleared</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="text-muted">
                                            {{ $deadline ? $deadline->format('M d, Y') : '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

