@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Fee Structures</h3>
        <a href="{{ route('finance.fee-structures.import') }}" class="btn btn-success">
            <i class="fas fa-upload me-1"></i>Import Fee Structures
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Manage Form --}}
    <form action="{{ route('finance.fee-structures.manage') }}" method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="classroom_id" class="form-label">Select Class</label>
            <select name="classroom_id" id="classroom_id" class="form-control" required>
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Manage Structure</button>
        </div>
    </form>

    <hr>

    <h5>Replicate Fee Structure</h5>
    <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <label>Source Fee Structure</label>
                <select name="source_structure_id" class="form-control">
                    <option value="">Select Fee Structure...</option>
                    @foreach($structures as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->name ?? ($s->classroom->name ?? 'N/A') }}
                            @if($s->student_category)
                                ({{ $s->student_category->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Or select source class below</small>
            </div>
            <div class="col-md-4">
                <label>Source Class (Alternative)</label>
                <select name="source_classroom_id" class="form-control">
                    <option value="">Select Class...</option>
                    @foreach($classrooms as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label>Student Category (Optional)</label>
                <select name="student_category_id" class="form-control">
                    <option value="">All Categories (General)</option>
                    @foreach(\App\Models\StudentCategory::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Leave blank for general structures</small>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <label>Target Classes (hold Ctrl to select multiple)</label>
                <select name="target_classroom_ids[]" class="form-control" multiple required>
                    @foreach($classrooms as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-warning">Replicate Fee Structure</button>
        </div>
    </form>
</div>
@endsection
