<div class="tab-pane fade" id="tab-ids" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">ID Prefix & Counters</h5>
                <div class="section-note">Control how staff and student IDs are generated.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-hash"></i> Identity</span>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.ids.save') }}" method="POST" class="row g-4">
                @csrf
                <div class="col-md-6">
                    <label for="staff_id_prefix" class="form-label fw-semibold">Staff ID Prefix</label>
                    <input type="text" name="staff_id_prefix" id="staff_id_prefix" class="form-control"
                           value="{{ setting('staff_id_prefix', 'STAFF') }}" required>
                    <div class="form-note mt-1">Example: STAFF → STAFF1001</div>
                </div>
                <div class="col-md-6">
                    <label for="staff_id_start" class="form-label fw-semibold">Staff ID Start</label>
                    <input type="number" name="staff_id_start" id="staff_id_start" class="form-control"
                           value="{{ setting('staff_id_start', '1001') }}" min="1" required>
                </div>
                <div class="col-md-6">
                    <label for="student_id_prefix" class="form-label fw-semibold">Student ID Prefix</label>
                    <input type="text" name="student_id_prefix" id="student_id_prefix" class="form-control"
                           value="{{ setting('student_id_prefix', 'STD') }}" required>
                    <div class="form-note mt-1">Example: STD → STD1000</div>
                </div>
                <div class="col-md-6">
                    <label for="student_id_start" class="form-label fw-semibold">Student ID Start</label>
                    <input type="number" name="student_id_start" id="student_id_start" class="form-control"
                           value="{{ setting('student_id_start', '1000') }}" min="1" required>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-settings-primary px-4">Save ID Settings</button>
                    <span class="form-note d-flex align-items-center gap-1"><i class="bi bi-shield-lock"></i> Counters update automatically when IDs are issued.</span>
                </div>
            </form>
        </div>
    </div>
</div>
