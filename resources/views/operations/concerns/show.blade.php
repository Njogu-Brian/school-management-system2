@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 800px;">
    <a href="{{ route('operations.concerns.index') }}" class="text-decoration-none">&larr; Back</a>
    <div class="d-flex justify-content-between align-items-start mt-2 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $concern->student?->full_name }}</h1>
            <p class="text-muted mb-0">{{ ucfirst($concern->category) }} · {{ $concern->student?->admission_number }}</p>
        </div>
        <span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',$concern->status)) }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6">Description</h2>
            <p class="mb-0" style="white-space: pre-wrap;">{{ $concern->description }}</p>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6">Concerned staff</h2>
            <ul class="mb-0">
                @forelse($concern->concernedStaff as $s)
                    <li>{{ $s->full_name ?? ($s->first_name.' '.$s->last_name) }}</li>
                @empty
                    <li class="text-muted">None</li>
                @endforelse
            </ul>
        </div>
    </div>

    <form method="POST" action="{{ route('operations.concerns.update', $concern->id) }}" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label">Update status</label>
                <select name="status" class="form-select">
                    @foreach(['open','in_progress','resolved','closed'] as $st)
                        <option value="{{ $st }}" @selected($concern->status === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection
