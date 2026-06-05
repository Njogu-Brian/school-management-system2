@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb"><a href="{{ route('operations.assets.index') }}">Fixed assets</a></div>
            <h1>Register asset</h1>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <form method="POST" action="{{ route('operations.assets.store') }}" class="card-body">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Asset tag *</label>
                        <input type="text" name="asset_tag" class="form-control" required value="{{ old('asset_tag') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" value="{{ old('category') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            @foreach(['active', 'in_repair', 'retired', 'disposed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serial number</label>
                        <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase cost</label>
                        <input type="number" step="0.01" name="purchase_cost" class="form-control" value="{{ old('purchase_cost') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned staff</label>
                        <select name="assigned_staff_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" @selected(old('assigned_staff_id') == $member->id)>{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-settings-primary">Save asset</button>
                    <a href="{{ route('operations.assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
