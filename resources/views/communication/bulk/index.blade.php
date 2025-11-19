@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bulk Communication</h1>
        <div class="btn-group">
            <a href="{{ route('communication.bulk.create', ['type' => 'email']) }}" class="btn btn-primary">
                <i class="bi bi-envelope"></i> Send Bulk Email
            </a>
            <a href="{{ route('communication.bulk.create', ['type' => 'sms']) }}" class="btn btn-success">
                <i class="bi bi-chat"></i> Send Bulk SMS
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">
                Send messages to multiple recipients at once. You can target all students, selected students, or entire classrooms.
            </p>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>Note:</strong> Bulk communication uses your configured email/SMS services. 
                Make sure your communication settings are properly configured.
            </div>
        </div>
    </div>
</div>
@endsection

