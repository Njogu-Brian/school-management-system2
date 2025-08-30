@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Kitchen Recipients</h4>

    <a href="{{ route('attendance.kitchen.recipients.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Add Recipient
    </a>

    @if($recipients->isEmpty())
        <div class="alert alert-info">No recipients found. Add one above.</div>
    @else
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Label</th>
                    <th>Staff</th>
                    <th>Assigned Classes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recipients as $recipient)
                    <tr>
                        <td>{{ $recipient->label }}</td>
                        <td>{{ $recipient->staff->first_name ?? '' }} {{ $recipient->staff->last_name ?? '' }}</td>
                        <td>
                            @if(!empty($recipient->classroom_ids))
                                @foreach($recipient->classroom_ids as $classId)
                                    <span class="badge bg-secondary">{{ $classrooms[$classId] ?? 'Unknown' }}</span>
                                @endforeach
                            @else
                                <em>All Classes</em>
                            @endif
                        </td>
                        <td>
                            @if($recipient->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('attendance.kitchen.recipients.edit', $recipient->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('attendance.kitchen.recipients.destroy', $recipient->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this recipient?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
