@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Stream Management</h2>
            <small class="text-muted">Manage streams and assign them to multiple classrooms</small>
        </div>
        <a href="{{ route('academics.streams.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Stream
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> All Streams</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Stream Name</th>
                            <th>Primary Classroom</th>
                            <th>Additional Classrooms</th>
                            <th>Teachers</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($streams as $stream)
                            <tr>
                                <td class="fw-semibold">
                                    <span class="badge bg-info fs-6">{{ $stream->name }}</span>
                                </td>
                                <td>
                                    @if($stream->classroom)
                                        <span class="badge bg-primary">{{ $stream->classroom->name }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($stream->classrooms->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($stream->classrooms as $classroom)
                                                <span class="badge bg-secondary">{{ $classroom->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        // Get all teachers for this stream across all classrooms
                                        $streamTeachers = \DB::table('stream_teacher')
                                            ->where('stream_id', $stream->id)
                                            ->join('users', 'stream_teacher.teacher_id', '=', 'users.id')
                                            ->select('users.name')
                                            ->distinct()
                                            ->get();
                                    @endphp
                                    @if($streamTeachers->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($streamTeachers as $teacher)
                                                <span class="badge bg-success">{{ $teacher->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">No teachers assigned</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('academics.streams.edit', $stream->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteStream({{ $stream->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $stream->id }}" action="{{ route('academics.streams.destroy', $stream->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No streams found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteStream(id) {
    if (confirm('Are you sure you want to delete this stream? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection
