@extends('layouts.app')

@section('content')
<div class="container">
    <h4>📢 Announcements</h4>
    <a href="{{ route('announcements.create') }}" class="btn btn-success mb-3">+ New Announcement</a>

    @foreach($announcements as $announcement)
        <div class="card mb-3">
            <div class="card-body">
                <h5>{{ $announcement->title }}</h5>
                <p>{{ $announcement->content }}</p>
                <small>
                    @if($announcement->expires_at)
                        Expires: {{ $announcement->expires_at->format('F j, Y') }}
                    @else
                        No expiry
                    @endif
                    | Status: <strong>{{ $announcement->active ? 'Active' : 'Inactive' }}</strong>
                </small>
                <div class="mt-2">
                    <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
