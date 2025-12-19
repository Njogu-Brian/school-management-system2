@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Communication / Announcements</div>
                <h1>Announcements</h1>
                <p>Manage and view school announcements.</p>
            </div>
            @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                <a href="{{ route('announcements.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Announcement
                </a>
            @endif
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All announcements</h5>
                @if(method_exists($announcements, 'total'))
                    <span class="input-chip">{{ $announcements->total() }} total</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $announcement)
                                <tr>
                                    <td class="fw-semibold">{{ $announcement->title }}</td>
                                    <td><span class="pill-badge">{{ $announcement->active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        @if($announcement->expires_at)
                                            {{ $announcement->expires_at->format('M j, Y') }}
                                        @else
                                            <span class="text-muted">No expiry</span>
                                        @endif
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-ghost-strong" type="button" data-bs-toggle="collapse" data-bs-target="#annBody{{ $announcement->id }}">
                                            View
                                        </button>
                                        @if(!auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher'))
                                            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger" onclick="return confirm('Delete this announcement?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="collapse" id="annBody{{ $announcement->id }}">
                                    <td colspan="4">
                                        <div class="p-3 text-muted">{!! nl2br(e($announcement->content)) !!}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No announcements available.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($announcements, 'links') && $announcements->hasPages())
                    <div class="p-3">
                        {{ $announcements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

