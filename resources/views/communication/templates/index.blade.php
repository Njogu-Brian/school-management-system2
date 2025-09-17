@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>Communication Templates</h4>
        <a href="{{ route('communication-templates.create') }}" class="btn btn-primary">+ Add Template</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-striped align-middle">
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
                    <td><code>{{ $t->code }}</code></td>
                    <td>{{ $t->title }}</td>
                    <td class="text-uppercase">{{ $t->type }}</td>
                    <td>{{ $t->subject ?? '—' }}</td>
                    <td>
                        @if($t->attachment)
                            <a href="{{ asset('storage/'.$t->attachment) }}" target="_blank">View</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{!! Str::limit(strip_tags($t->content), 80) !!}</td>
                    <td>
                        <a href="{{ route('communication-templates.edit', $t->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('communication-templates.destroy', $t->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this template?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No templates found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $templates->links() }}
    </div>
</div>
@endsection
