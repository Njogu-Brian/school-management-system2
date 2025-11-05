@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Report Cards</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th width="160">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report_cards as $rc)
                    <tr>
                        <td>{{ $rc->student->full_name }}</td>
                        <td>{{ $rc->classroom->name ?? '' }} {{ $rc->stream->name ?? '' }}</td>
                        <td>{{ $rc->term->name ?? '' }}</td>
                        <td>{{ $rc->academicYear->year ?? '' }}</td>
                        <td>
                            @if($rc->locked_at)
                                <span class="badge bg-danger">Locked</span>
                            @elseif($rc->published_at)
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-warning">Draft</span>
                            @endif
                        </td>
                        <td>{{ $rc->published_at ? $rc->published_at->format('d M Y') : '-' }}</td>
                        <td>
                            <a href="{{ route('academics.report_cards.show',$rc) }}" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if(!$rc->locked_at)
                                <a href="{{ route('academics.report_cards.edit',$rc) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('academics.report_cards.destroy',$rc) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this report card?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No report cards found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $report_cards->links() }}
    </div>
</div>
@endsection
