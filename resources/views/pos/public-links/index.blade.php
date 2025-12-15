@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Public Shop Links</h1>
            <p class="text-muted mb-0">Generate and manage public access links for the school shop</p>
        </div>
        <a href="{{ route('pos.public-links.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create Link
        </a>
    </div>

    @include('partials.alerts')

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Access Type</th>
                            <th>Student/Class</th>
                            <th>URL</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($links as $link)
                            <tr>
                                <td>{{ $link->name ?? 'Untitled Link' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($link->access_type) }}</span>
                                </td>
                                <td>
                                    @if($link->student)
                                        {{ $link->student->first_name }} {{ $link->student->last_name }}
                                    @elseif($link->classroom)
                                        {{ $link->classroom->name }}
                                    @else
                                        <span class="text-muted">Public</span>
                                    @endif
                                </td>
                                <td>
                                    <code class="small">{{ $link->getUrl() }}</code>
                                    <button class="btn btn-sm btn-link p-0 ms-1" onclick="copyToClipboard('{{ $link->getUrl() }}')" title="Copy URL">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                                <td>{{ $link->usage_count }} times</td>
                                <td>
                                    @if($link->isValid())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Expired</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('pos.public-links.edit', $link) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('pos.public-links.regenerate-token', $link) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" title="Regenerate Token">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('pos.public-links.destroy', $link) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this link?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No links found</p>
                                    <a href="{{ route('pos.public-links.create') }}" class="btn btn-primary btn-sm">Create First Link</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($links->hasPages())
            <div class="card-footer">
                {{ $links->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copied to clipboard!');
    });
}
</script>
@endsection



