<div class="tab-pane fade" id="tab-features" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Feature Toggles</h5>
                <div class="section-note">Gradually roll out capabilities to your team.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-toggle-on"></i> Beta</span>
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
                <div class="p-3 border rounded">
                    <div class="fw-semibold mb-1">Communication name style</div>
                    <div class="form-note mb-2">
                        Default for <code>@{{student_name}}</code> / <code>@{{staff_name}}</code> in SMS, email, and WhatsApp.
                        Lists, receipts, and reports always use First Middle Last.
                        You can still insert <code>@{{student_first_name}}</code> or <code>@{{student_full_name}}</code> in any template.
                    </div>
                    @php $commNameStyle = setting('communication_name_style', 'full'); @endphp
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
                <div>
                    <button class="btn btn-settings-primary px-4">Save Feature Toggles</button>
                    <span class="form-note d-inline-flex align-items-center gap-1 ms-2">
                        <i class="bi bi-info-circle"></i> Toggles save to settings instantly.
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
