<div class="tab-pane fade show active" id="tab-general" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">School Profile</h5>
                <div class="section-note">Used across receipts, report headers, and communication templates.</div>
            </div>
            <span class="pill-badge">Brand identity</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.general') }}" class="row g-4">
                @csrf
                <div class="col-md-6">
                    <label class="form-label fw-semibold">School Name</label>
                    <input type="text" class="form-control" name="school_name"
                        value="{{ old('school_name', $settings['school_name']->value ?? '') }}" required>
                    <div class="form-note mt-1">Shown on invoices, receipts, and PDF exports.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">School Email</label>
                    <input type="email" class="form-control" name="school_email"
                        value="{{ old('school_email', $settings['school_email']->value ?? '') }}">
                    <div class="form-note mt-1">Used as the default reply-to for outbound communication.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" class="form-control" name="school_phone"
                        value="{{ old('school_phone', $settings['school_phone']->value ?? '') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea class="form-control" name="school_address" rows="2">{{ old('school_address', $settings['school_address']->value ?? '') }}</textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-settings-primary px-4">Save General Info</button>
                    <span class="form-note d-flex align-items-center gap-1">
                        <i class="bi bi-info-circle"></i> Changes reflect instantly across the system.
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
