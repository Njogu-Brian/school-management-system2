@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $event->title }}</h1>
        <div class="btn-group">
            @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                <a href="{{ route('events.edit', $event) }}" class="btn btn-primary">Edit</a>
                <form action="{{ route('events.destroy', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this event?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
            <a href="{{ route('events.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="200">Type:</th>
                    <td><span class="badge bg-info">{{ ucfirst($event->type) }}</span></td>
                </tr>
                <tr>
                    <th>Description:</th>
                    <td>{{ $event->description ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Start Date:</th>
                    <td>{{ $event->start_date->format('M d, Y') }}</td>
                </tr>
                @if($event->end_date)
                <tr>
                    <th>End Date:</th>
                    <td>{{ $event->end_date->format('M d, Y') }}</td>
                </tr>
                @endif
                @if(!$event->is_all_day && $event->start_time)
                <tr>
                    <th>Time:</th>
                    <td>
                        {{ date('H:i', strtotime($event->start_time)) }}
                        @if($event->end_time)
                            - {{ date('H:i', strtotime($event->end_time)) }}
                        @endif
                    </td>
                </tr>
                @endif
                @if($event->venue)
                <tr>
                    <th>Venue:</th>
                    <td>{{ $event->venue }}</td>
                </tr>
                @endif
                <tr>
                    <th>Visibility:</th>
                    <td><span class="badge bg-secondary">{{ ucfirst($event->visibility) }}</span></td>
                </tr>
                @if($event->academicYear)
                <tr>
                    <th>Academic Year:</th>
                    <td>{{ $event->academicYear->year }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

