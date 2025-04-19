@extends('layouts.app')
@section('content')
<div class="container">
    <h4>📆 Scheduled Messages</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Message</th>
                <th>Scheduled At</th>
                <th>Type</th>
                <th>Target</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheduled as $item)
                <tr>
                    <td>{{ $item->template->title ?? 'N/A' }}</td>
                    <td>{{ Str::limit(strip_tags($item->template->content), 60) }}</td>
                    <td>{{ $item->scheduled_at->format('M d, Y H:i') }}</td>
                    <td>{{ strtoupper($item->type) }}</td>
                    <td>{{ ucfirst($item->target_type) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
