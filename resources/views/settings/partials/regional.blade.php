<div class="tab-pane fade" id="tab-regional" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Regional Defaults</h5>
                <div class="section-note">Applies to invoices, reminders, and date displays.</div>
            </div>
            <span class="pill-badge">Localization</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.regional') }}" class="row g-4">
                @csrf
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Timezone</label>
                    <input type="text" class="form-control" name="timezone"
                        placeholder="e.g. Africa/Nairobi"
                        value="{{ old('timezone', $settings['timezone']->value ?? '') }}">
                    <div class="form-note mt-1">Example: Africa/Nairobi</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Currency</label>
                    <input type="text" class="form-control" name="currency"
                        placeholder="e.g. KES"
                        value="{{ old('currency', $settings['currency']->value ?? '') }}">
                    <div class="form-note mt-1">Use ISO currency code (KES, USD, GBP).</div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-settings-primary px-4">Save Regional Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
