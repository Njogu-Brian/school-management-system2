@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Voteheads</h4>
    <a href="{{ route('voteheads.create') }}" class="btn btn-primary mb-3">Add Votehead</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Mandatory</th>
                <th>Charge Frequency</th> <!-- NEW COLUMN -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($voteheads as $votehead)
                <tr>
                    <td>{{ $votehead->name }}</td>
                    <td>{{ $votehead->description }}</td>
                    <td>{{ $votehead->is_mandatory ? 'Yes' : 'No' }}</td>
                    <td>
                        @switch($votehead->charge_type)
                            @case('per_student') Per Student @break
                            @case('once') Charge Once @break
                            @case('once_annually') Annually @break
                            @case('per_family') Per Family @break
                            @default Unknown
                        @endswitch
                    </td>
                    <td>
                        <a href="{{ route('voteheads.edit', $votehead) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('voteheads.destroy', $votehead) }}" method="POST" style="display:inline-block;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
