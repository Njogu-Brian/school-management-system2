@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Public Links</div>
                <h1>Public Shop Links</h1>
                <p>Manage shareable links for the public shop.</p>
            </div>
            <a href="{{ route('pos.public-links.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> New Link
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Links</h5>
                <span class="input-chip">{{ $links->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Token</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($links as $link)
                                <tr>
                                    <td>{{ $link->name }}</td>
                                    <td><span class="input-chip">{{ Str::limit($link->token, 10) }}</span></td>
                                    <td><span class="pill-badge">{{ $link->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>{{ $link->expires_at ? $link->expires_at->format('M d, Y') : '—' }}</td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('pos.shop.public', $link->token) }}" target="_blank" class="btn btn-sm btn-ghost-strong">Open</a>
                                        <a href="{{ route('pos.public-links.edit', $link) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('pos.public-links.destroy', $link) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this link?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No links found</p>
                                        <a href="{{ route('pos.public-links.create') }}" class="btn btn-settings-primary btn-sm">Create Link</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($links->hasPages())
                    <div class="p-3">
                        {{ $links->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

