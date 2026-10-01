@extends('operator.layout')
@section('title', 'Schools')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3">Schools</h1>
    <a href="{{ route('operator.schools.create') }}" class="btn btn-primary">Create school</a>
</div>
<div class="card">
    <table class="table mb-0">
        <thead><tr><th>Code</th><th>Name</th><th>Slug</th><th>Status</th><th>DB</th><th></th></tr></thead>
        <tbody>
        @foreach($schools as $s)
            <tr>
                <td><code>{{ $s->code }}</code></td>
                <td>{{ $s->name }}</td>
                <td>{{ $s->slug }}</td>
                <td>{{ $s->status }}</td>
                <td><small>{{ $s->db_name }}</small></td>
                <td><a href="{{ route('operator.schools.show', $s) }}">Manage</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $schools->links() }}
@endsection
