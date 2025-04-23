<div class="tab-pane fade show active" id="general" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.general') }}">
        @csrf
        <div class="mb-3">
            <label>School Name</label>
            <input type="text" class="form-control" name="school_name"
                value="{{ old('school_name', $settings['school_name']->value ?? '') }}">
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea class="form-control" name="school_address">{{ old('school_address', $settings['school_address']->value ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input type="text" class="form-control" name="school_phone"
                value="{{ old('school_phone', $settings['school_phone']->value ?? '') }}">
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="school_email"
                value="{{ old('school_email', $settings['school_email']->value ?? '') }}">
        </div>

        <button class="btn btn-primary">Save General Info</button>
    </form>
</div>
