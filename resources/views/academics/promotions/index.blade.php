@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Student Promotions</h2>
            <small class="text-muted">Promote students to next class based on class mapping</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Class Mapping & Promotions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Next Class</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classrooms as $classroom)
                            @php
                                // Check if this class has been promoted in the current academic year
                                $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
                                $alreadyPromoted = false;
                                if ($currentYear) {
                                    $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
                                        ->where('academic_year_id', $currentYear->id)
                                        ->where('promotion_status', 'promoted')
                                        ->exists();
                                }
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $classroom->name }}</td>
                                <td>
                                    @if($classroom->is_alumni)
                                        <span class="text-muted">
                                            <i class="bi bi-trophy"></i> Graduation → Alumni
                                        </span>
                                    @elseif($classroom->nextClass)
                                        <span class="text-success">
                                            <i class="bi bi-arrow-right"></i> {{ $classroom->nextClass->name }}
                                        </span>
                                    @else
                                        <span class="text-danger">
                                            <i class="bi bi-exclamation-triangle"></i> Not Mapped
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $classroom->students->count() }} students</span>
                                </td>
                                <td>
                                    @if($alreadyPromoted)
                                        <span class="badge bg-warning">
                                            <i class="bi bi-check-circle"></i> Already Promoted
                                        </span>
                                    @elseif($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                                        <span class="badge bg-success">
                                            <i class="bi bi-circle"></i> Ready
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-dash-circle"></i> Not Ready
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                                        @if($alreadyPromoted)
                                            <span class="text-muted small">Already promoted this year</span>
                                        @else
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('academics.promotions.show', $classroom) }}" class="btn btn-sm btn-primary" title="Select Students">
                                                    <i class="bi bi-arrow-up-circle"></i> Promote
                                                </a>
                                                <form action="{{ route('academics.promotions.promote-all', $classroom) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to promote ALL students from {{ $classroom->name }}?');">
                                                    @csrf
                                                    <input type="hidden" name="academic_year_id" value="{{ $currentYear?->id }}">
                                                    <input type="hidden" name="term_id" value="{{ $currentTerm?->id }}">
                                                    <input type="hidden" name="promotion_date" value="{{ date('Y-m-d') }}">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Promote All Students">
                                                        <i class="bi bi-arrow-up-circle-fill"></i> Promote All
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">No students</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No classrooms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

