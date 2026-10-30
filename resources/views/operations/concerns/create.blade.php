@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-shell" style="max-width: 720px;">
        <div class="page-header">
            <div class="crumb">
                <a href="{{ route('operations.concerns.index') }}" class="text-decoration-none">Operations</a>
                · Concerns
            </div>
            <h1>Raise a concern</h1>
            <p>Log an issue and notify assigned staff by SMS, email, and push.</p>
        </div>

        @include('partials.alerts')

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('operations.concerns.store') }}" class="settings-card">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Student ID <span class="text-danger">*</span></label>
                    <input type="number" name="student_id" class="form-control" value="{{ old('student_id') }}" required
                           placeholder="Enter student database ID">
                    <div class="form-text">Find the ID from the Students module or student profile URL.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Problem description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="5" class="form-control" required>{{ old('description') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Assigned staff <span class="text-danger">*</span></label>
                    <select name="staff_ids[]" class="form-select" multiple size="8" required>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}" @selected(collect(old('staff_ids', []))->contains($s->id))>
                                {{ $s->full_name ?? ($s->first_name.' '.$s->last_name) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple. Assigned staff can reply from the Users app.</div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('operations.concerns.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-settings-primary">Save &amp; notify</button>
            </div>
        </form>
    </div>
</div>
@endsection
