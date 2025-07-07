@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Bulk Upload Preview</h2>

    <form action="{{ route('students.bulk.import') }}" method="POST">
        @csrf

        <div class="alert alert-info">
            Review the records below. Students with missing or duplicate admission numbers will be auto-assigned.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Admission Number</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>Classroom</th>
                        <th>Parent Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                        <tr class="{{ $student['valid'] ? '' : 'table-danger' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student['admission_number'] ?? 'Auto' }}</td>
                            <td>{{ $student['first_name'] }}</td>
                            <td>{{ $student['last_name'] }}</td>
                            <td>{{ $student['gender'] }}</td>
                            <td>{{ $student['dob'] }}</td>
                            <td>{{ $student['classroom_name'] }}</td>
                            <td>{{ $student['father_phone'] ?? '-' }}</td>
                            <td>
                                @if ($student['valid'])
                                    <span class="badge bg-success">Ready</span>
                                @else
                                    <span class="badge bg-danger">Invalid</span>
                                @endif
                            </td>
                            <input type="hidden" name="students[]" value="{{ base64_encode(json_encode($student)) }}">
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-success" {{ !$allValid ? 'disabled' : '' }}>
            <i class="bi bi-upload"></i> Confirm & Import
        </button>
    </form>
</div>
@endsection
