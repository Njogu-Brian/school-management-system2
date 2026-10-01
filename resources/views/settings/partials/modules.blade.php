<div class="tab-pane fade" id="tab-modules" role="tabpanel">
    @php
        $moduleCatalog = $moduleCatalog ?? [];
        $submoduleCatalog = $submoduleCatalog ?? [];
        $enabledModules = $enabledModules ?? [];
        $enabledCount = count(array_filter(array_keys($moduleCatalog), fn ($k) => in_array($k, $enabledModules, true)));
        $totalModules = count($moduleCatalog);
        $modulesByGroup = collect($moduleCatalog)->groupBy(fn ($m) => $m['group'] ?? 'Other');
        $submodulesByGroup = collect($submoduleCatalog)->groupBy(fn ($m) => $m['group'] ?? 'Other');
    @endphp

    <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Modules & Navigation</h5>
                <div class="section-note">Choose which modules are visible in the sidebar for everyone with role access.</div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="pill-badge"><i class="bi bi-grid"></i> {{ $enabledCount }}/{{ $totalModules }} enabled</span>
                <span class="input-chip"><i class="bi bi-shield-check"></i> Applies after save</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.modules') }}" id="modules-preferences-form">
                @csrf
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="modules-enable-all">Enable all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="modules-disable-optional">Disable optional</button>
                </div>

                @foreach($modulesByGroup as $group => $items)
                    <div class="mb-3">
                        <div class="fw-semibold text-muted small text-uppercase mb-2">{{ $group }}</div>
                        <div class="row g-3">
                            @foreach($items as $key => $meta)
                                @php
                                    $isEnabled = in_array($key, $enabledModules, true);
                                    $locked = !empty($meta['locked']) || in_array($key, config('modules.always_on', []), true);
                                @endphp
                                <div class="col-md-4">
                                    <div class="h-100 p-3 border rounded d-flex justify-content-between align-items-start gap-2 {{ $locked ? 'bg-light' : '' }}">
                                        <div>
                                            <div class="fw-semibold">
                                                @if(!empty($meta['icon']))<i class="bi {{ $meta['icon'] }} me-1"></i>@endif
                                                {{ $meta['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                            </div>
                                            <div class="form-note mt-1">{{ $meta['description'] ?? 'Hide when not in use to simplify navigation.' }}</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input module-toggle" type="checkbox" name="modules[]" value="{{ $key }}"
                                                   id="module_{{ $key }}" data-module-key="{{ $key }}"
                                                   {{ $isEnabled || $locked ? 'checked' : '' }}
                                                   {{ $locked ? 'disabled' : '' }}>
                                            @if($locked)
                                                <input type="hidden" name="modules[]" value="{{ $key }}">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="mb-1">Sub-modules</h6>
                        <div class="section-note">Fine-tune areas inside an enabled parent module. Turning off a parent hides its sub-modules.</div>
                    </div>
                    <span class="pill-badge"><i class="bi bi-diagram-3"></i> {{ count($submoduleCatalog) }} areas</span>
                </div>

                @foreach($submodulesByGroup as $group => $items)
                    <div class="mb-3">
                        <div class="fw-semibold text-muted small text-uppercase mb-2">{{ $group }}</div>
                        <div class="row g-3">
                            @foreach($items as $key => $meta)
                                @php
                                    $isEnabled = in_array($key, $enabledModules, true);
                                    $parent = $meta['parent'] ?? '';
                                @endphp
                                <div class="col-md-4">
                                    <div class="h-100 p-3 border rounded d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $meta['label'] ?? $key }}</div>
                                            <div class="form-note mt-1">{{ $meta['description'] ?? '' }}</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input submodule-toggle" type="checkbox" name="modules[]" value="{{ $key }}"
                                                   id="module_{{ str_replace('.', '_', $key) }}"
                                                   data-parent="{{ $parent }}"
                                                   {{ $isEnabled ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

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
                <div class="section-note">Enable or disable capabilities that change behaviour, not only navigation.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-toggle-on"></i> Feature flags</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.features') }}" class="d-flex flex-column gap-3">
                @csrf
                <div class="d-flex justify-content-between align-items-start p-3 border rounded">
                    <div>
                        <div class="fw-semibold">Enable Online Admission</div>
                        <div class="form-note">Turn on self-service applications for guardians and the Online Admissions admin queue.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enable_online_admission" value="1" {{ feature_enabled('enable_online_admission') ? 'checked' : '' }}>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-start p-3 border rounded">
                    <div>
                        <div class="fw-semibold">Enable Communication Logs</div>
                        <div class="form-note">Show outbound email/SMS/WhatsApp logs in Communication.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enable_communication_logs" value="1" {{ feature_enabled('enable_communication_logs') ? 'checked' : '' }}>
                    </div>
                </div>
                @php $commNameStyle = setting('communication_name_style', 'full'); @endphp
                <div class="p-3 border rounded">
                    <div class="fw-semibold mb-1">Communication name style</div>
                    <div class="form-note mb-2">
                        Default for <code>@{{student_name}}</code> / <code>@{{staff_name}}</code> in SMS, email, and WhatsApp.
                        Lists, receipts, and reports always use First Middle Last.
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="communication_name_style" id="comm_name_full" value="full" {{ $commNameStyle !== 'first' ? 'checked' : '' }}>
                            <label class="form-check-label" for="comm_name_full">Full name (First Middle Last)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="communication_name_style" id="comm_name_first" value="first" {{ $commNameStyle === 'first' ? 'checked' : '' }}>
                            <label class="form-check-label" for="comm_name_first">First name only</label>
                        </div>
                    </div>
                </div>
                @php $googleLinkMode = setting('google_link_prompt_mode', 'all'); @endphp
                <div class="p-3 border rounded">
                    <div class="fw-semibold mb-1">Mobile Google link prompt</div>
                    <div class="form-note mb-2">
                        After password/OTP login, ask users to link Google (Skip always available).
                        Fine-tune specific users under Students → Google sign-in prompt.
                    </div>
                    <select name="google_link_prompt_mode" class="form-select" style="max-width: 28rem;">
                        <option value="all" @selected($googleLinkMode === 'all')>Everyone not yet linked</option>
                        <option value="selected" @selected($googleLinkMode === 'selected')>Selected users only</option>
                        <option value="off" @selected($googleLinkMode === 'off')>Off</option>
                    </select>
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

<script>
(function () {
    const form = document.getElementById('modules-preferences-form');
    if (!form) return;

    const syncSubmodules = () => {
        form.querySelectorAll('.submodule-toggle').forEach((el) => {
            const parent = el.getAttribute('data-parent');
            if (!parent) return;
            const parentEl = form.querySelector('.module-toggle[data-module-key="' + parent + '"]');
            const parentOn = parentEl ? parentEl.checked : true;
            el.disabled = !parentOn;
            if (!parentOn) el.checked = false;
        });
    };

    form.querySelectorAll('.module-toggle').forEach((el) => {
        el.addEventListener('change', syncSubmodules);
    });

    const enableAll = document.getElementById('modules-enable-all');
    if (enableAll) {
        enableAll.addEventListener('click', () => {
            form.querySelectorAll('.module-toggle:not(:disabled), .submodule-toggle').forEach((el) => {
                el.checked = true;
                el.disabled = false;
            });
            syncSubmodules();
        });
    }

    const disableOptional = document.getElementById('modules-disable-optional');
    if (disableOptional) {
        disableOptional.addEventListener('click', () => {
            form.querySelectorAll('.module-toggle:not(:disabled)').forEach((el) => { el.checked = false; });
            form.querySelectorAll('.submodule-toggle').forEach((el) => { el.checked = false; });
            syncSubmodules();
        });
    }

    syncSubmodules();
})();
</script>
