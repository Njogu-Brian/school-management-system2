@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Voteheads',
        'icon' => 'bi bi-list-ul',
        'subtitle' => 'Manage fee categories and voteheads',
        'actions' => '<a href="' . route('finance.voteheads.import') . '" class="btn btn-finance btn-finance-success"><i class="bi bi-upload"></i> Import Voteheads</a><a href="' . route('finance.voteheads.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Votehead</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Mandatory</th>
                        <th>Charge Frequency</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($voteheads as $votehead)
                        <tr>
                            <td><strong>{{ $votehead->name }}</strong></td>
                            <td>{{ $votehead->description ?? '—' }}</td>
                            <td>
                                @if($votehead->is_mandatory)
                                    <span class="finance-badge badge-approved">Yes</span>
                                @else
                                    <span class="finance-badge badge-pending">No</span>
                                @endif
                            </td>
                            <td>
                                @switch($votehead->charge_type)
                                    @case('per_student') 
                                        <span class="badge bg-info">Per Student</span>
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
                                        <span class="badge bg-warning">Unknown</span>
                                @endswitch
                            </td>
                            <td>
                                <div class="finance-action-buttons">
                                    <a href="{{ route('finance.voteheads.edit', $votehead) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('finance.voteheads.destroy', $votehead) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this votehead?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-list-ul"></i>
                                    </div>
                                    <h4>No voteheads found</h4>
                                    <p class="text-muted mb-3">Create your first votehead to get started</p>
                                    <a href="{{ route('finance.voteheads.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Add Votehead
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
