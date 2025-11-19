@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">My Children Diaries</h1>
    </div>

    <div class="row g-3">
        @forelse($students as $student)
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1">{{ $student->getNameAttribute() }}</h5>
                        <p class="text-muted mb-2">{{ $student->classroom->name ?? 'No class assigned' }}</p>
                        <p class="mb-3 text-muted small">Admission: {{ $student->admission_number }}</p>

                        <div class="mb-3 flex-grow-1">
                            @if($student->diary?->latestEntry)
                                <div class="text-truncate">
                                    <strong>Last Entry:</strong> {{ \Illuminate\Support\Str::limit($student->diary->latestEntry->content, 80) }}
                                </div>
                                <small class="text-muted">Updated {{ $student->diary->latestEntry->created_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">No diary entries yet.</span>
                            @endif
                        </div>

                        <a href="{{ route('parent.diaries.show', $student) }}" class="btn btn-primary w-100 mt-auto">
                            Open Diary
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No linked students found.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection

