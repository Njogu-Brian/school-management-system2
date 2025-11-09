@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h4 mb-4">Edit Family</h1>

                    <form action="{{ route('families.update', $family) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $family->guardian_name) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $family->phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $family->email) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Linked Students</label>
                            <select name="student_ids[]" class="form-select" multiple size="6">
                                @foreach ($availableStudents as $student)
                                    <option value="{{ $student->id }}" {{ $family->students->contains('id', $student->id) ? 'selected' : '' }}>
                                        {{ $student->full_name }} {{ $student->classroom ? '('.$student->classroom->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select students to attach to this family group.</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('families.index') }}" class="btn btn-link">Back to list</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Current Students</h2>
                    <form action="{{ route('families.destroy', $family) }}" method="POST" onsubmit="return confirm('Delete this family? Only allowed when no students are linked.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete Family</button>
                    </form>
                </div>
                <div class="card-body">
                    @if($family->students->isEmpty())
                        <p class="text-muted mb-0">No students currently linked.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($family->students as $student)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>
                                        {{ $student->full_name }}
                                        <span class="text-muted small">
                                            {{ $student->classroom?->name ?? 'No class' }}
                                        </span>
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        {{ $student->admission_number }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
