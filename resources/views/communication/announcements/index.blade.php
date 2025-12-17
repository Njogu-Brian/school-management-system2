@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Announcements',
        'icon' => 'bi bi-megaphone',
        'subtitle' => 'Manage and view school announcements',
        'actions' => (!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher')) 
            ? '<a href="' . route('announcements.create') . '" class="btn btn-comm btn-comm-primary"><i class="bi bi-plus-circle"></i> New Announcement</a>'
            : ''
    ])

    @forelse($announcements as $announcement)
        <div class="comm-card comm-animate">
            <div class="comm-card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-2">{{ $announcement->title }}</h5>
                        <div class="d-flex gap-3 align-items-center mb-2">
                            @if($announcement->expires_at)
                                <span class="badge bg-info">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Expires: {{ $announcement->expires_at->format('M j, Y') }}
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="bi bi-infinity me-1"></i>
                                    No expiry
                                </span>
                            @endif
                            <span class="badge {{ $announcement->active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $announcement->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                        <div class="d-flex gap-2">
                            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-comm btn-comm-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-comm btn-comm-danger" onclick="return confirm('Delete this announcement?')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="text-muted">
                    {!! nl2br(e($announcement->content)) !!}
                </div>
            </div>
        </div>
    @empty
        <div class="comm-card comm-animate">
            <div class="comm-card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-3 mb-0 text-muted">No announcements available.</p>
            </div>
        </div>
    @endforelse

    @if(method_exists($announcements, 'links'))
        <div class="mt-3">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
@endsection

