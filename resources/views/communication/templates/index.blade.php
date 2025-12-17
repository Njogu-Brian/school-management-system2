@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Communication Templates',
        'icon' => 'bi bi-file-text',
        'subtitle' => 'Manage SMS and email templates for automated communications',
        'actions' => '<a href="' . route('communication-templates.create') . '" class="btn btn-comm btn-comm-primary"><i class="bi bi-plus-circle"></i> Add Template</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success comm-alert alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger comm-alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <div class="table-responsive">
                <table class="table comm-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Attachment</th>
                            <th>Preview</th>
                            <th style="width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $t)
                            <tr>
                                <td><code class="bg-light px-2 py-1 rounded">{{ $t->code }}</code></td>
                                <td><strong>{{ $t->title }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $t->type === 'sms' ? 'info' : 'primary' }} text-uppercase">
                                        {{ $t->type }}
                                    </span>
                                </td>
                                <td>{{ $t->subject ?? '—' }}</td>
                                <td>
                                    @if($t->attachment)
                                        <a href="{{ asset('storage/'.$t->attachment) }}" target="_blank" class="btn btn-sm btn-comm-outline">
                                            <i class="bi bi-paperclip"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{!! Str::limit(strip_tags($t->content), 80) !!}</small></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('communication-templates.edit', $t->id) }}" class="btn btn-sm btn-comm btn-comm-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('communication-templates.destroy', $t->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-comm btn-comm-danger" onclick="return confirm('Delete this template?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mt-3 mb-0">No templates found. Create your first template to get started.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($templates->hasPages())
            <div class="mt-3">
                {{ $templates->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
