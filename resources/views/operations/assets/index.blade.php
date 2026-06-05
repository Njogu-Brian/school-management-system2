@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Operations</div>
                <h1>Fixed assets register</h1>
                <p>School equipment, furniture, and capital assets.</p>
            </div>
            <a href="{{ route('operations.assets.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> Register asset
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Assigned to</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $asset)
                            <tr>
                                <td>{{ $asset->asset_tag }}</td>
                                <td>{{ $asset->name }}</td>
                                <td>{{ $asset->category ?? '—' }}</td>
                                <td>{{ $asset->location ?? '—' }}</td>
                                <td>{{ $asset->assignedStaff?->full_name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $asset->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No assets registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $assets->links() }}</div>
        </div>
    </div>
</div>
@endsection
