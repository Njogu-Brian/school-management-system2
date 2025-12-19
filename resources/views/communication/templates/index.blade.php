@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Templates',
            'icon' => 'bi bi-layout-text-window',
            'subtitle' => 'Reusable email/SMS templates',
            'actions' => '<a href="' . route('communication-templates.create') . '" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> New Template</a>'
        ])

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Templates</h5>
                <span class="input-chip">{{ $templates->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Channel</th>
                                <th>Updated</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td class="fw-semibold">{{ $template->title }}</td>
                                    <td><span class="pill-badge">{{ strtoupper($template->type ?? 'N/A') }}</span></td>
                                    <td>{{ $template->updated_at?->format('M d, Y') }}</td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('communication-templates.edit', $template) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('communication-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No templates found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($templates->hasPages())
                    <div class="p-3">
                        {{ $templates->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

