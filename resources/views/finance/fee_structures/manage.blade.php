@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Manage Fee Structure</h3>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Class selection --}}
    <form method="GET" action="{{ route('finance.fee-structures.manage') }}" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <label>Select Class</label>
                <select name="classroom_id" class="form-control" required onchange="this.form.submit()">
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

    @if($selectedClassroom)
    {{-- Fee Structure Form --}}
    <form action="{{ route('finance.fee-structures.save') }}" method="POST">
        @csrf

        <input type="hidden" name="classroom_id" value="{{ $selectedClassroom }}">
        <div class="mb-3">
            <label>Year</label>
            <input type="number" name="year" class="form-control" value="{{ $feeStructure->year ?? date('Y') }}" required>
        </div>

        <h5>Votehead Charges (Ksh)</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Votehead</th>
                    <th>Term 1</th>
                    <th>Term 2</th>
                    <th>Term 3</th>
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
                            {{ $votehead->name }}
                            <input type="hidden" name="charges[{{ $loop->index }}][votehead_id]" value="{{ $votehead->id }}">
                        </td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_1]" class="form-control" step="0.01" value="{{ $term1 }}"></td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_2]" class="form-control" step="0.01" value="{{ $term2 }}"></td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_3]" class="form-control" step="0.01" value="{{ $term3 }}"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <button type="submit" class="btn btn-primary">Save Fee Structure</button>
    </form>

    {{-- Replication Form --}}
    @if($feeStructure)
    <hr>
    <h5>Replicate this Fee Structure</h5>
    <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
        @csrf
        <input type="hidden" name="source_classroom_id" value="{{ $selectedClassroom }}">

        <div class="mb-3">
            <label>Select Target Classes (hold Ctrl to select multiple)</label>
            <select name="target_classroom_ids[]" class="form-control" multiple required>
                @foreach($classrooms->where('id', '!=', $selectedClassroom) as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-secondary">Replicate to Selected Classes</button>
    </form>
    @endif

    @endif
</div>
@endsection
