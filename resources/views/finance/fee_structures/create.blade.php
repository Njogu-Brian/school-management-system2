@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Fee Structure</h2>
    <form method="POST" action="{{ route('fee-structures.store') }}">
        @csrf

        <div class="mb-3">
            <label for="classroom_id">Classroom</label>
            <select name="classroom_id" class="form-control" required>
                <option value="">Select</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="year">Year</label>
            <input type="number" name="year" value="{{ date('Y') }}" class="form-control" required>
        </div>

        <hr>
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
                    <tr>
                        <td>
                            <input type="hidden" name="charges[{{ $loop->index }}][votehead_id]" value="{{ $votehead->id }}">
                            {{ $votehead->name }}
                        </td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_1]" class="form-control" step="0.01"></td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_2]" class="form-control" step="0.01"></td>
                        <td><input type="number" name="charges[{{ $loop->index }}][term_3]" class="form-control" step="0.01"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
