@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb"><a href="{{ route('operations.visitors.index') }}">Visitor log</a></div>
            <h1>Check in visitor</h1>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <form method="POST" action="{{ route('operations.visitors.store') }}" class="card-body">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Visitor name *</label>
                        <input type="text" name="visitor_name" class="form-control" required value="{{ old('visitor_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ID number</label>
                        <input type="text" name="id_number" class="form-control" value="{{ old('id_number') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Organization</label>
                        <input type="text" name="organization" class="form-control" value="{{ old('organization') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Badge number</label>
                        <input type="text" name="badge_number" class="form-control" value="{{ old('badge_number') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Host staff</label>
                        <select name="host_staff_id" class="form-select">
                            <option value="">— Select —</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" @selected(old('host_staff_id') == $member->id)>{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Host name (if not staff)</label>
                        <input type="text" name="host_name" class="form-control" value="{{ old('host_name') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-settings-primary">Check in</button>
                    <a href="{{ route('operations.visitors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
