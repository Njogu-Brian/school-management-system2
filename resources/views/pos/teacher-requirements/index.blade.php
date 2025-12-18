@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Teacher Requirements</div>
                <h1>Teacher Requirements</h1>
                <p>Manage required items for classes and track fulfillment.</p>
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Requirements</h5>
                <span class="input-chip">{{ $requirements->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Class</th>
                                <th>Item</th>
                                <th>Quantity/Student</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requirements as $requirement)
                                <tr>
                                    <td>{{ $requirement->classroom->name ?? '—' }}</td>
                                    <td>{{ $requirement->posProduct->name ?? '—' }}</td>
                                    <td>{{ $requirement->quantity_per_student }}</td>
                                    <td>{{ $requirement->unit }}</td>
                                    <td><span class="pill-badge">{{ $requirement->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('pos.teacher-requirements.show', $requirement) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No requirements found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($requirements->hasPages())
                    <div class="p-3">
                        {{ $requirements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

