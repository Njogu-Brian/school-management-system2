@extends('layouts.app')

@section('content')
    <div class="ds-pilot finance-pilot">
    <x-page-header eyebrow="Finance" title="Voteheads" description="Manage fee categories and voteheads" class="finance-hero mb-3">
        <x-slot:actions>
            <a href="{{ route('finance.voteheads.import') }}" class="btn btn-secondary"><i class="bi bi-upload" aria-hidden="true"></i> Import Voteheads</a>
            <a href="{{ route('finance.voteheads.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle" aria-hidden="true"></i> Add Votehead</a>
        </x-slot:actions>
    </x-page-header>

    <x-feedback.flash />

    <x-card class="finance-card finance-animate" flush>
        <x-data.table caption="Voteheads" class="finance-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Mandatory</th>
                        <th>Activity fee</th>
                        <th>Charge Frequency</th>
                            <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($voteheads as $votehead)
                        <tr>
                                <td class="fw-semibold">{{ $votehead->name }}</td>
                            <td>{{ $votehead->description ?? '—' }}</td>
                            <td>
                                @if($votehead->is_mandatory)
                                        <span class="badge bg-success">Yes</span>
                                @else
                                        <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                @if($votehead->is_activity_fee ?? false)
                                    <span class="badge bg-primary">Yes</span>
                                @else
                                    <span class="badge bg-light text-muted border">No</span>
                                @endif
                            </td>
                            <td>
                                @switch($votehead->charge_type)
                                    @case('per_student') 
                                            <span class="badge bg-info text-dark">Per Student</span>
                                        @break
                                    @case('once') 
                                        <span class="badge bg-secondary">Charge Once</span>
                                        @break
                                    @case('once_annually') 
                                        <span class="badge bg-primary">Annually</span>
                                        @break
                                    @case('per_family') 
                                        <span class="badge bg-success">Per Family</span>
                                        @break
                                    @default 
                                            <span class="badge bg-warning text-dark">Unknown</span>
                                @endswitch
                            </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('finance.voteheads.edit', $votehead) }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                    </a>
                                        <form id="delete-votehead-{{ $votehead->id }}" action="{{ route('finance.voteheads.destroy', $votehead) }}" method="POST">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-votehead-{{ $votehead->id }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Delete {{ $votehead->name }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="6" class="text-center py-4">
                                    <h5 class="mb-1">No voteheads found</h5>
                                    <p class="text-muted mb-3">Create your first votehead to get started</p>
                                    <a href="{{ route('finance.voteheads.create') }}" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Add Votehead
                                    </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
        </x-data.table>
    </x-card>
    @foreach($voteheads as $votehead)
        <x-feedback.confirmation-modal
            id="confirm-delete-votehead-{{ $votehead->id }}"
            title="Delete votehead?"
            :message="'This will delete ' . $votehead->name . '. This action cannot be undone.'"
            confirm-label="Delete votehead"
            form="delete-votehead-{{ $votehead->id }}" />
    @endforeach
    </div>
</div>
@endsection
