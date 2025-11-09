@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-4">Create Family</h1>

                    <form action="{{ route('families.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link Students</label>
                            <select name="student_ids[]" class="form-select" multiple size="6">
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->full_name }} {{ $student->classroom ? '('.$student->classroom->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple students.</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('families.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Family</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
