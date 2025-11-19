@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Backup & Restore</h1>
        <form action="{{ route('backup-restore.create') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-database"></i> Create Backup
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Available Backups</h5>
            @if(count($backups) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr>
                                    <td>{{ $backup['name'] }}</td>
                                    <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                    <td>{{ $backup['created_at']->format('M d, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('backup-restore.download', $backup['name']) }}" class="btn btn-sm btn-success">Download</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No backups found. Create your first backup using the button above.
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="mb-3">Restore Backup</h5>
            <form action="{{ route('backup-restore.restore') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Upload Backup File</label>
                    <input type="file" name="backup_file" class="form-control" accept=".sql,.zip" required>
                    <small class="text-muted">Upload a .sql or .zip backup file to restore</small>
                </div>
                <button type="submit" class="btn btn-warning">Restore Backup</button>
            </form>
        </div>
    </div>
</div>
@endsection

