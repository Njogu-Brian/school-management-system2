<div class="tab-pane fade" id="system" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.system') }}">
        @csrf

        <div class="mb-3">
            <label>System Version</label>
            <input type="text" class="form-control" name="system_version"
                value="{{ $settings['system_version']->value ?? '1.0.0' }}" readonly>
        </div>

        <div class="mb-3">
            <label>Enable Backup</label>
            <select class="form-select" name="enable_backup">
                @php
                    $enableBackup = $settings['enable_backup']->value ?? '0';
                @endphp
                <option value="1" {{ $enableBackup == '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $enableBackup == '0' ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <button class="btn btn-primary">Save System Options</button>
    </form>
</div>
