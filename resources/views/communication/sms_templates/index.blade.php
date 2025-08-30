@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>SMS Template List</h4>
        <a href="{{ route('sms-templates.create') }}" class="btn btn-primary">+ Add</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($templates as $template)
                @if($template->type === 'sms')
                <tr>
                    <td>{{ $template->title }}</td>
                    <td>{{ Str::limit($template->content, 100) }}</td>
                    <td>
                        <a href="{{ route('sms-templates.edit', $template->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('sms-templates.destroy', $template->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this SMS template?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection
