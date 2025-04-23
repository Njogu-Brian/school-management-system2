<div class="tab-pane fade" id="modules" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.modules') }}">
        @csrf
        <div class="mb-3">
            <label>Enabled Modules</label>
            @foreach($modules as $module)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module }}"
                        {{ in_array($module, $enabledModules) ? 'checked' : '' }}>
                    <label class="form-check-label">
                        {{ ucfirst(str_replace('_', ' ', $module)) }}
                    </label>
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary">Save Module Preferences</button>
    </form>
</div>
