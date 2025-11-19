@extends('layouts.app')

@section('content')
<div class="container">
    <h4>📢 Announcements</h4>
    @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
        <a href="{{ route('announcements.create') }}" class="btn btn-success mb-3">+ New Announcement</a>
    @endif

    @forelse($announcements as $announcement)
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
                @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                    <div class="mt-2">
                        <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-primary">Edit</a>
                        <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No announcements available.
        </div>
    @endforelse

    @if(method_exists($announcements, 'links'))
        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
@endsection

