@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Terms</h4>
    <a href="{{ route('terms.create') }}" class="btn btn-primary mb-3">Add Term</a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Year</th>
                <th>Current</th>
            </tr>
        </thead>
        <tbody>
            @foreach($terms as $term)
            <tr>
                <td>{{ $term->name }}</td>
                <td>{{ $term->academicYear->year }}</td>
                <td>{{ $term->is_current ? '✅' : '❌' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
