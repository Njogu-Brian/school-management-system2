<div class="tab-pane fade" id="tab-modules" role="tabpanel">
    @php
        $enabledCount = count($enabledModules ?? []);
        $totalModules = count($modules ?? []);
    @endphp

    <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Modules & Navigation</h5>
                <div class="section-note">Choose which modules are visible in the sidebar and throughout the app.</div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="pill-badge"><i class="bi bi-grid"></i> {{ $enabledCount }}/{{ $totalModules }} enabled</span>
                <span class="input-chip"><i class="bi bi-shield-check"></i> Applies instantly</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.modules') }}">
                @csrf
                <div class="row g-3">
                    @foreach($modules as $module)
                        @php
                            $isEnabled = in_array($module, $enabledModules);
                            $label = ucfirst(str_replace('_', ' ', $module));
                        @endphp
                        <div class="col-md-4">
                            <div class="h-100 p-3 border rounded d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $label }}</div>
                                    <div class="form-note mt-1">Hide modules not in use to simplify navigation.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module }}"
                                           id="module_{{ $module }}" {{ $isEnabled ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-settings-primary px-4">
                        <i class="bi bi-save"></i> Save Module Preferences
                    </button>
                    <span class="form-note d-inline-flex align-items-center gap-1">
                        <i class="bi bi-info-circle"></i> These preferences control sidebar visibility for all users with access.
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Feature Toggles</h5>
                <div class="section-note">Enable or disable beta capabilities without touching core settings.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-toggle-on"></i> Feature flags</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.features') }}" class="d-flex flex-column gap-3">
                @csrf
                <div class="d-flex justify-content-between align-items-start p-3 border rounded">
                    <div>
                        <div class="fw-semibold">Enable Online Admission</div>
                        <div class="form-note">Turn on self-service applications for guardians.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enable_online_admission" value="1" {{ setting('enable_online_admission') ? 'checked' : '' }}>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-start p-3 border rounded">
                    <div>
                        <div class="fw-semibold">Enable Communication Logs</div>
                        <div class="form-note">Track outbound emails and SMS messages per student.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enable_communication_logs" value="1" {{ setting('enable_communication_logs') ? 'checked' : '' }}>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button class="btn btn-settings-primary px-4">
                        <i class="bi bi-save"></i> Save Feature Toggles
                    </button>
                    <span class="form-note d-inline-flex align-items-center gap-1">
                        <i class="bi bi-lightning-charge"></i> Changes apply immediately to all users.
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
