@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Fee Structures',
        'icon' => 'bi bi-table',
        'subtitle' => 'Manage and replicate fee structures for classes',
        'actions' => '<a href="' . route('finance.fee-structures.import') . '" class="btn btn-finance btn-finance-success"><i class="bi bi-upload"></i> Import Fee Structures</a>'
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

    {{-- Manage Form --}}
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-search me-2"></i> Select Class to Manage
        </div>
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-structures.manage') }}" method="GET" class="row g-3">
                <div class="col-md-9">
                    <label for="classroom_id" class="finance-form-label">Select Class</label>
                    <select name="classroom_id" id="classroom_id" class="finance-form-select" required>
                        <option value="">-- Select Class --</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                        <i class="bi bi-arrow-right"></i> Manage Structure
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Replicate Form --}}
    <div class="finance-card finance-animate">
        <div class="finance-card-header secondary">
            <i class="bi bi-copy me-2"></i> Replicate Fee Structure
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="finance-form-label">Source Fee Structure</label>
                        <select name="source_structure_id" class="finance-form-select">
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
                        <label class="finance-form-label">Source Class (Alternative)</label>
                        <select name="source_classroom_id" class="finance-form-select">
                            <option value="">Select Class...</option>
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="finance-form-label">Student Category (Optional)</label>
                        <select name="student_category_id" class="finance-form-select">
                            <option value="">All Categories (General)</option>
                            @foreach(\App\Models\StudentCategory::all() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Leave blank for general structures</small>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-12">
                        <label class="finance-form-label">Target Classes (hold Ctrl to select multiple)</label>
                        <select name="target_classroom_ids[]" class="finance-form-select" multiple required style="height: 150px;">
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-finance btn-finance-warning">
                        <i class="bi bi-copy"></i> Replicate Fee Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
