@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Manage Fee Structure',
        'icon' => 'bi bi-table',
        'subtitle' => 'Configure fee structures for classes by term'
    ])

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Class selection --}}
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <i class="bi bi-funnel me-2"></i> Select Class
        </div>
        <div class="finance-card-body">
            <form method="GET" action="{{ route('finance.fee-structures.manage') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="finance-form-label">Select Class</label>
                        <select name="classroom_id" class="finance-form-select" required onchange="this.form.submit()">
                            <option value="">-- Choose Classroom --</option>
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassroom == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClassroom)
    {{-- Fee Structure Form --}}
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <i class="bi bi-pencil-square me-2"></i> Fee Structure for {{ $classrooms->firstWhere('id', $selectedClassroom)->name ?? 'Selected Class' }}
        </div>
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-structures.save') }}" method="POST">
                @csrf

                <input type="hidden" name="classroom_id" value="{{ $selectedClassroom }}">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" class="finance-form-control" value="{{ $feeStructure->year ?? date('Y') }}" required>
                    </div>
                </div>

                <h5 class="mb-3">Votehead Charges (Ksh)</h5>
                <div class="finance-table-wrapper">
                    <div class="table-responsive">
                        <table class="finance-table">
                            <thead>
                                <tr>
                                    <th>Votehead</th>
                                    <th class="text-center">Term 1</th>
                                    <th class="text-center">Term 2</th>
                                    <th class="text-center">Term 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voteheads as $votehead)
                                    @php
                                        $existing = collect($charges)->where('votehead_id', $votehead->id);
                                        $term1 = old("charges.{$loop->index}.term_1", $existing->where('term', 1)->first()->amount ?? '');
                                        $term2 = old("charges.{$loop->index}.term_2", $existing->where('term', 2)->first()->amount ?? '');
                                        $term3 = old("charges.{$loop->index}.term_3", $existing->where('term', 3)->first()->amount ?? '');
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $votehead->name }}</strong>
                                            <input type="hidden" name="charges[{{ $loop->index }}][votehead_id]" value="{{ $votehead->id }}">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_1]" class="finance-form-control text-center" step="0.01" value="{{ $term1 }}" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_2]" class="finance-form-control text-center" step="0.01" value="{{ $term2 }}" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_3]" class="finance-form-control text-center" step="0.01" value="{{ $term3 }}" placeholder="0.00">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-check-circle"></i> Save Fee Structure
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Replication Form --}}
    @if($feeStructure)
    <div class="finance-card finance-animate">
        <div class="finance-card-header secondary">
            <i class="bi bi-copy me-2"></i> Replicate this Fee Structure
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
                @csrf
                <input type="hidden" name="source_classroom_id" value="{{ $selectedClassroom }}">

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="finance-form-label">Select Target Classes (hold Ctrl to select multiple)</label>
                        <select name="target_classroom_ids[]" class="finance-form-select" multiple required style="height: 200px;">
                            @foreach($classrooms->where('id', '!=', $selectedClassroom) as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple classes</small>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-finance btn-finance-warning">
                        <i class="bi bi-copy"></i> Replicate to Selected Classes
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection
