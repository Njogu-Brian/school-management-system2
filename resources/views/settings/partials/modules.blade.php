<div class="tab-pane fade" id="tab-modules" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Module Access</h5>
                <div class="section-note">Toggle modules that should be visible to your team.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-grid"></i> Availability</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.modules') }}">
                @csrf
                <div class="row g-3">
                    @foreach($modules as $module)
                        <div class="col-md-4">
                            <div class="form-check border rounded p-3 h-100">
                                <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module }}"
                                    id="module_{{ $module }}" {{ in_array($module, $enabledModules) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="module_{{ $module }}">
                                    {{ ucfirst(str_replace('_', ' ', $module)) }}
                                </label>
                                <div class="form-note mt-1">Hide modules not in use to simplify navigation.</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-settings-primary px-4">Save Module Preferences</button>
                </div>
            </form>
        </div>
    </div>
</div>
