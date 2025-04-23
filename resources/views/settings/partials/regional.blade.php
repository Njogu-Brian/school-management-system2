<div class="tab-pane fade" id="regional" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.regional') }}">
        @csrf
        <div class="mb-3">
            <label>Timezone</label>
            <input type="text" class="form-control" name="timezone" 
                value="{{ old('timezone', $settings['timezone']->value ?? '') }}">
        </div>
        <div class="mb-3">
            <label>Currency</label>
            <input type="text" class="form-control" name="currency" 
                value="{{ old('currency', $settings['currency']->value ?? '') }}">
        </div>
        <button class="btn btn-primary">Save Regional Settings</button>
    </form>
</div>
