@extends('layouts.app')

@section('content')
<h1>SMS Logs</h1>

<table>
    <thead>
        <tr>
            <th>Phone Number</th>
            <th>Message</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($smsLogs as $log)
            <tr>
                <td>{{ $log->phone_number }}</td>
                <td>{{ $log->message }}</td>
                <td>{{ $log->created_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<form action="{{ route('admin.sms.logs.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="phone_number">Phone Number</label>
        <input type="text" name="phone_number" id="phone_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="message">Message</label>
        <textarea name="message" id="message" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Send SMS</button>
</form>
@endsection