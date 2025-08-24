<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Assign Optional Fee – Class-Based</h5>
    </div>

    <div class="card-body">
        {{-- Move your entire form block here --}}
        <form method="POST" action="{{ route('finance.optional_fees.save_class') }}">
            @csrf
            <input type="hidden" name="votehead_id" value="{{ request('votehead_id') }}">
            <input type="hidden" name="term" value="{{ $term }}">
            <input type="hidden" name="year" value="{{ $year }}">

            @php
                $selectedVotehead = $optionalVoteheads->firstWhere('id', request('votehead_id'));
                $defaultAmount = $selectedVotehead->default_amount ?? 0;
            @endphp

            <input type="hidden" name="amount" value="{{ $defaultAmount }}">

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Admission No</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $index => $student)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->full_name }}</td>
                                <td>{{ $student->admission_number }}</td>
                                <td class="text-center">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                            name="students[{{ $student->id }}]" id="bill_{{ $student->id }}"
                                            value="billed"
                                            {{ ($statuses[$student->id] ?? '') === 'billed' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="bill_{{ $student->id }}">Bill</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                            name="students[{{ $student->id }}]" id="exempt_{{ $student->id }}"
                                            value="exempt"
                                            {{ ($statuses[$student->id] ?? '') === 'exempt' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="exempt_{{ $student->id }}">Exempt</label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No students found for selected class.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-circle"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
