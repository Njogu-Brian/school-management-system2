@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Families &amp; Guardians</h1>
        <a href="{{ route('families.create') }}" class="btn btn-primary">Create Family</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Guardian</th>
                            <th>Contact</th>
                            <th class="text-center">Students</th>
                            <th class="text-end">Outstanding (KES)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($families as $family)
                            <tr>
                                <td>
                                    <strong>{{ $family->guardian_name }}</strong>
                                    <div class="small text-muted">
                                        {{ $family->students_count }} linked student{{ $family->students_count === 1 ? '' : 's' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $family->phone ?? '—' }}</div>
                                    <div class="small text-muted">{{ $family->email ?? 'No email' }}</div>
                                </td>
                                <td class="text-center">
                                    @if($family->students->isNotEmpty())
                                        <div class="small">
                                            @foreach($family->students as $student)
                                                <span class="d-block">{{ $student->full_name }} ({{ $student->classroom?->name ?? 'No class' }})</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">No students linked</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @php $balance = $balances[$family->id] ?? 0; @endphp
                                    <span class="{{ $balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ number_format($balance, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('families.edit', $family) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No families recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $families->links() }}
        </div>
    </div>
</div>
@endsection
