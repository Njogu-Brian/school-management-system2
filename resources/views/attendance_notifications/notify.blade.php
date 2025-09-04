@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">📢 Attendance Notification</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('attendance.notifications.notify.send') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Select Date</label>
                        <input type="date" name="date" value="{{ $date ?? now()->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="force" value="1" class="form-check-input" id="forceSend">
                            <label class="form-check-label text-danger fw-bold" for="forceSend">
                                Force Send (ignore incomplete attendance)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                        <button type="submit" class="btn btn-purple">
                            <i class="bi bi-send"></i> Send Notification
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Status --}}
    <div class="alert {{ $isComplete ? 'alert-success' : 'alert-warning' }}">
        <i class="bi bi-info-circle"></i>
        Attendance is <strong>{{ $isComplete ? 'Complete' : 'NOT Complete' }}</strong> for {{ $date ?? now()->toDateString() }}.
    </div>

    {{-- Attendance Summary --}}
    <div class="card shadow-sm">
        <div class="card-header fw-bold">Attendance Summary (Present)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Class</th>
                        <th>Present Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryByClass as $class => $count)
                        <tr>
                            <td>{{ $class }}</td>
                            <td><span class="badge bg-success">{{ $count }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No attendance data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recipients --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold">Recipients</div>
        <div class="card-body">
            @forelse($recipients as $r)
                <p>
                    <strong>{{ $r->label }}</strong> → 
                    {{ $r->staff->first_name ?? '' }} {{ $r->staff->last_name ?? '' }} 
                    ({{ $r->staff->phone_number ?? 'No phone' }})
                </p>
            @empty
                <p class="text-muted">No recipients defined.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-purple {
        background-color: #5a189a;
        color: white;
        border-radius: 6px;
    }
    .btn-purple:hover {
        background-color: #3c096c;
        color: #fff;
    }
</style>
@endpush
