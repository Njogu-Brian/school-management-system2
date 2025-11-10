@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Academic Years & Terms</h4>

    @if(session('success')) 
        <div class="alert alert-success">{{ session('success') }}</div> 
    @endif

    <div class="mb-3">
        <a href="{{ route('settings.academic.year.create') }}" class="btn btn-success">➕ Add Academic Year</a>
        <a href="{{ route('settings.academic.term.create') }}" class="btn btn-primary">➕ Add Term</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Year</th>
                <th>Active</th>
                <th>Terms</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $year)
            <tr>
                <td>{{ $year->year }}</td>
                <td>{{ $year->is_active ? '✅' : '❌' }}</td>
                <td>
                    <div class="row g-2">
                        @foreach($year->terms as $term)
                        <div class="col-12">
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ $term->name }}</strong>
                                            {!! $term->is_current ? '<span class="badge bg-success ms-2">Current</span>' : '' !!}
                                            <div class="small text-muted mt-1">
                                                @if($term->opening_date && $term->closing_date)
                                                    <div><i class="bi bi-calendar-event"></i> Opens: {{ $term->opening_date->format('M d, Y') }}</div>
                                                    <div><i class="bi bi-calendar-x"></i> Closes: {{ $term->closing_date->format('M d, Y') }}</div>
                                                    @if($term->midterm_start_date && $term->midterm_end_date)
                                                        <div><i class="bi bi-calendar-range"></i> Midterm: {{ $term->midterm_start_date->format('M d') }} - {{ $term->midterm_end_date->format('M d, Y') }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-warning">Dates not set</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <a href="{{ route('settings.academic.term.edit', $term) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form action="{{ route('settings.academic.term.destroy', $term) }}" method="POST" style="display:inline;">
                                                @csrf @method('DELETE')
                                                <button onclick="return confirm('Delete term?')" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </td>
                <td>
                    <a href="{{ route('settings.academic.year.edit', $year) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('settings.academic.year.destroy', $year) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Delete year?')" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
