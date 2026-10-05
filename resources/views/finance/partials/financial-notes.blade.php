{{--
  Financial notes panel for student/family context.
  Required: $student (model) OR ($studentId + $familyId)
--}}
@php
    $fnStudent = $student ?? null;
    $fnStudentId = $fnStudent?->id ?? ($studentId ?? null);
    $fnFamilyId = $fnStudent?->family_id ?? ($familyId ?? null);
    $financialNotes = \App\Models\FinancialNote::forStudentContext(
        $fnStudentId ? (int) $fnStudentId : null,
        $fnFamilyId ? (int) $fnFamilyId : null
    )->limit(50)->get();
    $canEditNotes = auth()->check();
@endphp

@if($fnStudentId || $fnFamilyId)
<div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4" id="financial-notes-panel">
    <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-sticky"></i> Financial Notes
        </h5>
        <small class="text-muted">Student &amp; family — promise dates, follow-ups, adjustments</small>
    </div>
    <div class="finance-card-body p-4">
        @if($financialNotes->isEmpty())
            <p class="text-muted mb-3 mb-md-4">No financial notes yet.</p>
        @else
            <div class="list-group list-group-flush mb-3">
                @foreach($financialNotes as $note)
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                @if($note->is_pinned)
                                    <span class="badge text-bg-warning me-1">Pinned</span>
                                @endif
                                @if($note->student_id)
                                    <span class="badge text-bg-secondary me-1">Student</span>
                                @else
                                    <span class="badge text-bg-info me-1">Family</span>
                                @endif
                                @if($note->promise_date)
                                    <span class="badge text-bg-primary me-1">
                                        Promise {{ $note->promise_date->format('d M Y') }}
                                    </span>
                                @endif
                                <div class="mt-1" style="white-space: pre-wrap;">{{ $note->body }}</div>
                                <small class="text-muted">
                                    {{ $note->creator?->name ?? 'Staff' }}
                                    · {{ $note->created_at?->format('d M Y H:i') }}
                                </small>
                            </div>
                            @if($canEditNotes)
                                <form method="POST" action="{{ route('finance.financial-notes.destroy', $note) }}" onsubmit="return confirm('Remove this note?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($canEditNotes)
            <form method="POST" action="{{ route('finance.financial-notes.store') }}" class="row g-2">
                @csrf
                <input type="hidden" name="student_id" value="{{ $fnStudentId }}">
                <input type="hidden" name="family_id" value="{{ $fnFamilyId }}">
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                <div class="col-12">
                    <label class="form-label">Add note</label>
                    <textarea name="body" class="form-control" rows="2" required maxlength="5000" placeholder="e.g. Parent promised to clear balance by Friday; adjust invoice after bursary…"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Promise date</label>
                    <input type="date" name="promise_date" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="fnPin{{ $fnStudentId }}">
                        <label class="form-check-label" for="fnPin{{ $fnStudentId }}">Pin note</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                        <i class="bi bi-plus-circle"></i> Save note
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endif
