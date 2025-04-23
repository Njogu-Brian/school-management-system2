<div class="tab-pane fade" id="features" role="tabpanel">
    <h5 class="mt-3">Feature Toggles</h5>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="enable_online_admission" value="1" {{ setting('enable_online_admission') ? 'checked' : '' }}>
        <label class="form-check-label">Enable Online Admission</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="enable_communication_logs" value="1" {{ setting('enable_communication_logs') ? 'checked' : '' }}>
        <label class="form-check-label">Enable Communication Logs</label>
    </div>
</div>
