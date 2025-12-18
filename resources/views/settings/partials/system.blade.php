<div class="tab-pane fade" id="tab-system" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">System Options</h5>
                <div class="section-note">Backup policy and current release version.</div>
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
                    @php $enableBackup = $settings['enable_backup']->value ?? '0'; @endphp
                    <select class="form-select" name="enable_backup">
                        <option value="1" {{ $enableBackup == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ $enableBackup == '0' ? 'selected' : '' }}>No</option>
                    </select>
                    <div class="form-note mt-1">Toggle automated backups for core data.</div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-settings-primary px-4">Save System Options</button>
                </div>
            </form>
        </div>
    </div>
</div>
