@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Skills - {{ $reportCard->student->full_name }}</h1>

    <a href="{{ route('academics.report_cards.skills.create',$reportCard) }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus"></i> Add Skill
    </a>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Skill</th>
                    <th>Rating</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skills as $skill)
                    <tr>
                        <td>{{ $skill->skill_name }}</td>
                        <td>{{ $skill->rating }}</td>
                        <td>
                            <a href="{{ route('academics.report_cards.skills.edit',[$reportCard,$skill]) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('academics.report_cards.skills.destroy',[$reportCard,$skill]) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete skill?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No skills assigned.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
