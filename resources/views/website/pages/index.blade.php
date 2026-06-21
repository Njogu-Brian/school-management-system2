@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Pages',
            'icon' => 'bi bi-file-earmark-text',
            'subtitle' => 'Manage public website pages and slugs',
            'actions' => '<a href="' . route('website.pages.create') . '" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> New Page</a>',
        ])

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Homepage</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pages as $page)
                                <tr>
                                    <td class="fw-semibold">{{ $page->name }}</td>
                                    <td><code>/{{ $page->slug }}</code></td>
                                    <td><span class="pill-badge">{{ ucfirst($page->status) }}</span></td>
                                    <td>@if($page->is_homepage)<i class="bi bi-house-fill text-success"></i>@endif</td>
                                    <td class="text-end">
                                        <a href="{{ route('website.pages.edit', $page) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                        @if(!$page->is_homepage)
                                            <form action="{{ route('website.pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No pages yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pages->hasPages())
                    <div class="p-3">{{ $pages->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
