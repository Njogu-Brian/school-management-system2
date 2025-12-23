@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Voteheads',
        'icon' => 'bi bi-list-ul',
        'subtitle' => 'Manage fee categories and voteheads',
        'actions' => '<a href="' . route('finance.voteheads.import') . '" class="btn btn-outline-primary"><i class="bi bi-upload"></i> Import Voteheads</a><a href="' . route('finance.voteheads.create') . '" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Votehead</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="finance-card finance-animate">
        <div class="card-body">
        <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Mandatory</th>
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
                                        <form action="{{ route('finance.voteheads.destroy', $votehead) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this votehead?');">
                                        @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="5" class="text-center py-4">
                                    <h5 class="mb-1">No voteheads found</h5>
                                    <p class="text-muted mb-3">Create your first votehead to get started</p>
                                    <a href="{{ route('finance.voteheads.create') }}" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Add Votehead
                                    </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
