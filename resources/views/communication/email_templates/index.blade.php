@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>Email Template List</h4>
        <a href="{{ route('email-templates.create') }}" class="btn btn-primary">+ Add</a>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Message</th>
                <th>Attachment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($emailTemplates as $template)
                <tr>
                    <td>{{ $template->title }}</td>
                    <td>{{ Str::limit(strip_tags($template->message), 80) }}</td>
                    <td>
                        @if($template->attachment)
                            <a href="{{ asset('storage/' . $template->attachment) }}" target="_blank">View</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('email-templates.edit', $template->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('email-templates.destroy', $template->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this template?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
