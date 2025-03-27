@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Online Admissions</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Reference No</th>
                <th>Student Name</th>
                <th>Date of Birth</th>
                <th>Gender</th>
                <th>Form Status</th>
                <th>Payment Status</th>
                <th>Enrolled</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($admissions as $admission)
                <tr>
                    <td>{{ $admission->id }}</td>
                    <td>{{ $admission->first_name }} {{ $admission->last_name }}</td>
                    <td>{{ $admission->dob }}</td>
                    <td>{{ $admission->gender }}</td>
                    <td>
                        <span class="badge {{ $admission->form_status === 'Submitted' ? 'bg-success' : 'bg-danger' }}">
                            {{ $admission->form_status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $admission->payment_status === 'Paid' ? 'bg-success' : 'bg-danger' }}">
                            {{ $admission->payment_status }}
                        </span>
                    </td>
                    <td>{{ $admission->enrolled ? 'Yes' : 'No' }}</td>
                    <td>
                        @if (!$admission->enrolled)
                            <form action="{{ route('online-admissions.approve', $admission->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form action="{{ route('online-admissions.reject', $admission->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        @else
                            <span class="text-success">Enrolled</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
