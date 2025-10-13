@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Behaviour Categories</h1>

    <a href="{{ route('academics.behaviours.create') }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus"></i> Add Category
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th width="150">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($behaviours as $behaviour)
                <tr>
                    <td>{{ $behaviour->name }}</td>
                    <td>{{ $behaviour->description }}</td>
                    <td>
                        <a href="{{ route('academics.behaviours.edit',$behaviour) }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('academics.behaviours.destroy',$behaviour) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No behaviour categories defined.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
