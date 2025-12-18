@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Backup</div>
                <h1>Backup & Restore</h1>
                <p>Safeguard data with on-demand and scheduled backups.</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="settings-chip"><i class="bi bi-shield-check"></i> Admin only</span>
                </div>
            </div>
            <form action="{{ route('backup-restore.create') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-settings-primary">
                    <i class="bi bi-cloud-arrow-up"></i> Run Backup Now
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Schedule</h5>
                    <div class="section-note">Automatic database backups.</div>
                </div>
                <span class="input-chip">Last run: {{ $schedule['last_run'] ? \Carbon\Carbon::parse($schedule['last_run'])->diffForHumans() : 'Never' }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('backup-restore.schedule') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Frequency</label>
                        <select name="frequency" class="form-select">
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'biweekly' => 'Bi-weekly', 'monthly' => 'Monthly'] as $key => $label)
                                <option value="{{ $key }}" {{ ($schedule['frequency'] ?? 'weekly') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Time (24h)</label>
                        <input type="time" name="time" class="form-control" value="{{ $schedule['time'] ?? '02:00' }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-settings-primary"><i class="bi bi-save"></i> Save Schedule</button>
                        <a href="{{ route('backup-restore.create') }}" class="btn btn-ghost-strong"><i class="bi bi-play-fill"></i> Run now</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Available Backups</h5>
                <span class="input-chip">{{ count($backups) }} files</span>
            </div>
            <div class="card-body p-0">
                @if(count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                    <tr>
                                        <td class="fw-semibold">{{ $backup['name'] }}</td>
                                        <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                        <td>{{ $backup['created_at']->format('M d, Y H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('backup-restore.download', $backup['name']) }}" class="btn btn-sm btn-ghost-strong">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-3"></i>
                        <div class="mt-2">No backups found. Create your first backup using the button above.</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Restore Backup</h5>
                <span class="pill-badge">SQL uploads supported</span>
            </div>
            <div class="card-body">
                <form action="{{ route('backup-restore.restore') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">Upload Backup File</label>
                        <input type="file" name="backup_file" class="form-control" accept=".sql,.zip" required>
                        <small class="text-muted">Upload a .sql file to restore. Zip restore is not yet supported.</small>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

