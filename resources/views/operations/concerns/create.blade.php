@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 720px;">
    <a href="{{ route('operations.concerns.index') }}" class="text-decoration-none">&larr; Back</a>
    <h1 class="h4 mt-2 mb-3">Raise a concern</h1>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('operations.concerns.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body row g-3">
            <div class="col-12">
                <label class="form-label">Student ID <span class="text-danger">*</span></label>
                <input type="number" name="student_id" class="form-control" value="{{ old('student_id') }}" required
                       placeholder="Enter student database ID (search from Students module)">
                <div class="form-text">Use the student registry to find the ID, or paste from student profile URL.</div>
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
                <label class="form-label">Problem raised by parent <span class="text-danger">*</span></label>
                <textarea name="description" rows="5" class="form-control" required>{{ old('description') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Concerned staff</label>
                <select name="staff_ids[]" class="form-select" multiple size="8">
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}" @selected(collect(old('staff_ids', []))->contains($s->id))>
                            {{ $s->full_name ?? ($s->first_name.' '.$s->last_name) }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Selected staff receive SMS (no details), email, and push notification.</div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('operations.concerns.index') }}" class="btn btn-light">Cancel</a>
            <button class="btn btn-primary">Save &amp; notify</button>
        </div>
    </form>
</div>
@endsection
