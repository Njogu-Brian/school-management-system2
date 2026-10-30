@extends('layouts.app')

@section('content')
@php
    $statusTone = [
        'open' => 'warning',
        'in_progress' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary',
    ];
@endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Operations</div>
                <h1>Student concerns</h1>
                <p>Parent-raised issues, staff replies, and follow-up status.</p>
            </div>
            <a href="{{ route('operations.concerns.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> Raise concern
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search student</label>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or admission number…">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['open','in_progress','resolved','closed'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <div class="settings-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Raised by</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Staff</th>
                            <th>Raised</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($concerns as $concern)
                            @php $raised = $raiser->present($concern); @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $concern->student?->full_name }}</div>
                                    <div class="small text-muted">
                                        {{ $concern->student?->admission_number }}
                                        @if($concern->student?->classroom?->name)
                                            · {{ $concern->student->classroom->name }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $raised['label'] }}</span>
                                </td>
                                <td><span class="badge bg-secondary">{{ ucfirst($concern->category) }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $statusTone[$concern->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_',' ',$concern->status)) }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $concern->concernedStaff->map(fn ($s) => $s->full_name ?? trim($s->first_name.' '.$s->last_name))->filter()->join(', ') ?: '—' }}
                                </td>
                                <td class="small text-nowrap">{{ $concern->created_at?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('operations.concerns.show', $concern->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No concerns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($concerns->hasPages())
                <div class="p-3 border-top">{{ $concerns->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
