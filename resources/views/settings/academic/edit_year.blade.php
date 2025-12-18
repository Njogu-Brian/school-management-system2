@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Academic Calendar</div>
                <h1>Edit Academic Year</h1>
                <p>Update the status or name of this academic year.</p>
            </div>
            <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost"><i class="bi bi-arrow-left"></i> Back to calendar</a>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Year Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.academic.year.update', $year) }}" method="POST" class="row g-4">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label for="year" class="form-label fw-semibold">Year</label>
                        <input type="text" name="year" id="year" value="{{ $year->year }}" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"{{ old('is_active', $year->is_active ?? false) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">Set as Active Year</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary px-4">Update Year</button>
                        <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
