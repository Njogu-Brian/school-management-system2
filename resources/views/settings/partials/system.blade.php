<div class="tab-pane fade" id="tab-system" role="tabpanel">
    @php
        $enableBackup = $settings['enable_backup']->value ?? '0';
        $canManageBackups = auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Admin');
    @endphp

    <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">System Options</h5>
                <div class="section-note">Release info and global backup toggle.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-cpu"></i> Platform</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.system') }}" class="row g-4 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label fw-semibold">System Version</label>
                    <input type="text" class="form-control" name="system_version"
                        value="{{ $settings['system_version']->value ?? '1.0.0' }}" readonly>
                    <div class="form-note mt-1">Version is managed by deployments.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Enable Backup</label>
                    <select class="form-select" name="enable_backup">
                        <option value="1" {{ $enableBackup == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ $enableBackup == '0' ? 'selected' : '' }}>No</option>
                    </select>
                    <div class="form-note mt-1">Turn backup automation on or off for this environment.</div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-settings-primary px-4">Save System Options</button>
                </div>
            </form>
        </div>
    </div>

    <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Backup Schedule & Automation</h5>
                <div class="section-note">Keep your database backed up on a predictable cadence.</div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <span class="pill-badge"><i class="bi bi-clock-history"></i> Last run: {{ $backupSchedule['last_run'] ? \Carbon\Carbon::parse($backupSchedule['last_run'])->diffForHumans() : 'Never' }}</span>
                @unless($canManageBackups)
                    <span class="input-chip"><i class="bi bi-lock"></i> Admins only</span>
                @endunless
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('backup-restore.schedule') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Frequency</label>
                    <select name="frequency" class="form-select" {{ $canManageBackups ? '' : 'disabled' }}>
                        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'biweekly' => 'Bi-weekly', 'monthly' => 'Monthly'] as $key => $label)
                            <option value="{{ $key }}" {{ ($backupSchedule['frequency'] ?? 'weekly') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Time (24h)</label>
                    <input type="time" name="time" class="form-control" value="{{ $backupSchedule['time'] ?? '02:00' }}" {{ $canManageBackups ? '' : 'disabled' }}>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-settings-primary flex-fill" {{ $canManageBackups ? '' : 'disabled' }}>
                        <i class="bi bi-calendar-check"></i> Save Schedule
                    </button>
                    <button type="submit" form="run-backup-now" class="btn btn-ghost-strong w-100" {{ $canManageBackups ? '' : 'disabled' }}>
                        <i class="bi bi-cloud-arrow-up"></i> Run Backup Now
                    </button>
                </div>
            </form>
            <form id="run-backup-now" action="{{ route('backup-restore.create') }}" method="POST" class="d-none">
                @csrf
            </form>
            <div class="form-note mt-2">Scheduled backups run based on the server timezone.</div>
        </div>
    </div>

    <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Recent Backups</h5>
                <div class="section-note">Download or verify the latest backup files.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-hdd-stack"></i> Storage</span>
        </div>
        <div class="card-body">
            @if(empty($backups))
                <div class="subtle-hero">
                    <i class="bi bi-info-circle"></i> No backups found yet. Create one to get started.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Created</th>
                                <th>Size</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($backups, 0, 5) as $backup)
                                <tr>
                                    <td class="fw-semibold">{{ $backup['name'] }}</td>
                                    <td>{{ $backup['created_at']->diffForHumans() }}</td>
                                    <td>{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                    <td class="text-end">
                                        <a href="{{ route('backup-restore.download', $backup['name']) }}" class="btn btn-sm btn-ghost-strong" {{ $canManageBackups ? '' : 'disabled' }}>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Restore Backup</h5>
                <div class="section-note">Upload a .sql export to restore your database.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-arrow-counterclockwise"></i> Restore</span>
        </div>
        <div class="card-body">
            <form action="{{ route('backup-restore.restore') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Upload Backup File</label>
                    <input type="file" name="backup_file" class="form-control" accept=".sql,.zip" {{ $canManageBackups ? '' : 'disabled' }} required>
                    <small class="text-muted">Upload a .sql file to restore. Zip restore is not yet supported.</small>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-warning w-100" {{ $canManageBackups ? '' : 'disabled' }}>
                        <i class="bi bi-shield-shaded"></i> Restore
                    </button>
                </div>
            </form>
            @unless($canManageBackups)
                <div class="form-note mt-2 text-danger">You need admin privileges to restore or download backups.</div>
            @endunless
        </div>
    </div>
</div>
