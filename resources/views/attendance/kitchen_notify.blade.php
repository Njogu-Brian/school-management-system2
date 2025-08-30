@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Notify Kitchen</h4>

    <form action="{{ route('attendance.kitchen.notify.send') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Select Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Attendance Summary (Preview)</label>
            <div class="border p-3 bg-light">
                <p><strong>Total Present: {{ array_sum($summaryByClass) }}</strong></p>
                <ul>
                    @foreach($summaryByClass as $class => $count)
                        <li>{{ $class }}: {{ $count }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Recipients</label>
            <ul>
                @foreach($recipients as $rec)
                    <li>
                        {{ $rec->label }} —
                        {{ $rec->staff->first_name ?? '' }} {{ $rec->staff->last_name ?? '' }}
                        ({{ $rec->staff->phone_number ?? 'No phone' }})
                    </li>
                @endforeach
            </ul>
        </div>

        @if(!$isComplete)
            <div class="alert alert-warning">
                Attendance not complete for all classes.  
                <label>
                    <input type="checkbox" name="force" value="1"> Force Send Anyway
                </label>
            </div>
        @endif

        <button type="submit" class="btn btn-success">
            <i class="bi bi-bell"></i> Send Notification
        </button>
        <a href="{{ route('attendance.mark.form') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
