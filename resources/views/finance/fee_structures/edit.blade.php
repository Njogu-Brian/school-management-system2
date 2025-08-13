@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ isset($feeStructure) ? 'Edit' : 'Create' }} Fee Structure</h3>

    <form action="{{ isset($feeStructure) ? route('fee-structures.update', $feeStructure) : route('fee-structures.store') }}" method="POST">
        @csrf
        @if(isset($feeStructure)) @method('PUT') @endif

        <div class="mb-3">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control" required>
                <option value="">Select...</option>
                @foreach($classrooms as $class)
                <option value="{{ $class->id }}" {{ (isset($feeStructure) && $feeStructure->classroom_id == $class->id) ? 'selected' : '' }}>
                    {{ $class->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Year</label>
            <input type="number" name="year" class="form-control" value="{{ old('year', $feeStructure->year ?? date('Y')) }}" required>
        </div>

        <hr>
        <h5>Charges</h5>
        <div id="charges-wrapper">
            @php $charges = $feeStructure->charges ?? [null]; @endphp
            @foreach($charges as $i => $charge)
            <div class="row charge-row mb-2">
                <div class="col-md-4">
                    <select name="charges[{{ $i }}][votehead_id]" class="form-control" required>
                        <option value="">Votehead</option>
                        @foreach($voteheads as $votehead)
                        <option value="{{ $votehead->id }}"
                            {{ isset($charge) && $charge->votehead_id == $votehead->id ? 'selected' : '' }}>
                            {{ $votehead->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="charges[{{ $i }}][term]" class="form-control" placeholder="Term" value="{{ $charge->term ?? 1 }}" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="charges[{{ $i }}][amount]" class="form-control" placeholder="Amount" value="{{ $charge->amount ?? '' }}" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-secondary" id="add-charge">+ Add Row</button>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">{{ isset($feeStructure) ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>

<script>
    let index = {{ count($charges) }};
    document.getElementById('add-charge').addEventListener('click', function () {
        let wrapper = document.getElementById('charges-wrapper');
        let html = `
        <div class="row charge-row mb-2">
            <div class="col-md-4">
                <select name="charges[\${index}][votehead_id]" class="form-control" required>
                    <option value="">Votehead</option>
                    @foreach($voteheads as $votehead)
                    <option value="{{ $votehead->id }}">{{ $votehead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="charges[\${index}][term]" class="form-control" placeholder="Term" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="charges[\${index}][amount]" class="form-control" placeholder="Amount" step="0.01" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
            </div>
        </div>`;
        wrapper.insertAdjacentHTML('beforeend', html);
        index++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.charge-row').remove();
        }
    });
</script>
@endsection
