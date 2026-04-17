@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Parent notification preferences',
            'subtitle' => 'Choose which parent (father or mother) does not receive automated school SMS, email, and WhatsApp. Only one can be excluded; the other must have at least one contact.',
            'icon' => 'bi bi-person-slash',
            'actions' => '<a href="' . route('communication.parent-notification-blocks.create') . '" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add preference</a>'
        ])

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="get" action="{{ route('communication.parent-notification-blocks.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Student name, admission, or parent name">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                        @if(request()->filled('search'))
                            <a href="{{ route('communication.parent-notification-blocks.index') }}" class="btn btn-ghost-strong">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Families with a parent excluded from automated notifications</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Excluded parent</th>
                                <th>Father / Mother (on file)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parents as $p)
                                @php
                                    $stu = $p->students->first();
                                @endphp
                                <tr>
                                    <td>
                                        @if($stu)
                                            <div class="fw-semibold">{{ $stu->full_name }}</div>
                                            <div class="small text-muted">{{ $stu->admission_number }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $stu?->classroom?->name ?? '—' }}</td>
                                    <td>
                                        <span class="pill-badge">{{ $p->school_notifications_muted_parent === 'father' ? 'Father' : 'Mother' }}</span>
                                    </td>
                                    <td class="small">
                                        <div><strong>F:</strong> {{ $p->father_name ?: '—' }}</div>
                                        <div><strong>M:</strong> {{ $p->mother_name ?: '—' }}</div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('communication.parent-notification-blocks.edit', $p) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No preferences set. Both parents receive notifications (where contacts exist), or use <strong>Add preference</strong>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($parents->hasPages())
                    <div class="card-body border-top">{{ $parents->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
