@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Legacy Import Batch #' . $batch->id,
        'icon' => 'bi bi-archive',
        'subtitle' => 'Review parsed terms and lines for this legacy PDF',
        'actions' => ''
    ])

    @if (session('success'))
        <div class="alert alert-success finance-animate">{{ session('success') }}</div>
    @endif

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 p-4 legacy-card">
        <div class="row gy-3 align-items-center">
            <div class="col-md-3">
                <div class="text-muted small">File</div>
                <div class="fw-semibold text-truncate" style="max-width: 100%;">{{ $batch->file_name }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Class</div>
                <div class="fw-semibold">{{ $batch->class_label ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Status</div>
                @php
                    $status = $batch->status;
                    $badgeClass = match($status) {
                        'approved' => 'bg-success-subtle text-success',
                        'pending_review' => 'bg-warning text-dark',
                        'running' => 'bg-info text-dark',
                        default => 'bg-secondary-subtle text-secondary'
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">
                    {{ ucfirst(str_replace('_',' ', $status)) }}
                </span>
                @if($status === 'running')
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
                    </div>
                    <div class="text-muted small mt-1">Parsing in progress…</div>
                @endif
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Students</div>
                <div class="fw-semibold">{{ $batch->total_students }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Drafts</div>
                @if($batch->draft_students > 0)
                    <span class="badge bg-warning text-dark">{{ $batch->draft_students }}</span>
                @else
                    <span class="text-muted">{{ $batch->draft_students }}</span>
                @endif
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('finance.legacy-imports.approve', $batch) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                            <i class="bi bi-check2-circle"></i> Approve Batch
                        </button>
                    </form>
                    @if($canApproveAll && $batch->status !== 'approved')
                        <form action="{{ route('finance.legacy-imports.approve-all', $batch) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-check2-all"></i> Approve All Students
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('finance.legacy-imports.rerun', $batch) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Re-run will delete parsed data for this batch and re-parse the PDF. Continue?')">
                            <i class="bi bi-arrow-repeat"></i> Re-run Parse
                        </button>
                    </form>
                    <form action="{{ route('finance.legacy-imports.destroy', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete batch, parsed data, and PDF? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete Batch & File
                        </button>
                    </form>
                </div>
                @if(!$canApproveAll)
                    <div class="text-muted small mt-1">Approve all is enabled when all students are mapped and drafts are cleared.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Votehead mappings + posting status --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 p-3 legacy-card">
        <div class="card-header d-flex justify-content-between align-items-center px-0 pt-0 border-0 bg-white flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Votehead Mapping & Posting Status</div>
                <div class="text-muted small">Map legacy voteheads to proceed with posting.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('finance.legacy-imports.report', $batch) }}" target="_blank">
                    <i class="bi bi-graph-up"></i> View Posting Report
                </a>
                <form action="{{ route('finance.legacy-imports.reverse-posting', $batch) }}" method="POST" onsubmit="return confirm('Reverse will delete finance records posted from this batch. Continue?');">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-arrow-counterclockwise"></i> Reverse Finance Posting
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body p-3">
            @if($pendingLabels->isNotEmpty())
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Pending votehead mappings: {{ implode(', ', $pendingLabels->toArray()) }}
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Resolve Votehead</h6>
                    <form action="{{ route('finance.legacy-imports.voteheads.resolve', $batch) }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Legacy Label</label>
                            <select name="legacy_label" class="form-select" required>
                                @forelse($pendingLabels as $label)
                                    <option value="{{ $label }}">{{ $label }}</option>
                                @empty
                                    <option value="">All voteheads mapped</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mode</label>
                            <select name="mode" class="form-select" id="modeSelect">
                                <option value="existing">Map to existing</option>
                                <option value="new">Create new</option>
                            </select>
                        </div>
                        <div class="col-12" id="existingBlock">
                            <label class="form-label">Existing Votehead</label>
                            <select name="votehead_id" class="form-select">
                                <option value="">Select</option>
                                @foreach($voteheads as $vh)
                                    <option value="{{ $vh->id }}">{{ $vh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12" id="newBlock" style="display:none;">
                            <label class="form-label">New Votehead Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Name">
                            <label class="form-label mt-2">Category</label>
                            <select name="votehead_category_id" class="form-select">
                                <option value="">(optional)</option>
                                @foreach($voteheadCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-settings-primary w-100">Save Mapping</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Posting Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Target</th><th>Status</th><th class="text-end">Count</th></tr>
                            </thead>
                            <tbody>
                                @forelse($postingSummary as $row)
                                    <tr>
                                        <td>{{ $row->target_type }}</td>
                                        <td><span class="badge bg-light text-dark text-uppercase">{{ $row->status }}</span></td>
                                        <td class="text-end">{{ $row->total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center">No postings yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($postingErrors->isNotEmpty())
                        <div class="mt-3">
                            <h6 class="fw-semibold">Errors / Skips (latest 20)</h6>
                            <ul class="list-group list-group-flush">
                                @foreach($postingErrors as $err)
                                    <li class="list-group-item small">
                                        <div><strong>{{ $err->target_type }}</strong> — {{ $err->status }}</div>
                                        <div class="text-muted">{{ $err->error_message ?? 'No message' }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Class-by-class processing --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 p-3 legacy-card">
        <div class="card-header px-0 pt-0 border-0 bg-white">
            <div class="fw-semibold">Process by Class</div>
            <div class="text-muted small">Queue posting for one class at a time.</div>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Class Label</th>
                            <th class="text-end">Students</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classGroups as $cg)
                            <tr>
                                <td>{{ $cg->class_label ?? 'Unspecified' }}</td>
                                <td class="text-end">{{ $cg->students_count }}</td>
                                <td class="text-end">
                                    <form action="{{ route('finance.legacy-imports.process-class', $batch) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="class_label" value="{{ $cg->class_label }}">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-play"></i> Process Class
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No class data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @forelse($grouped as $admission => $student)
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 p-3">
            <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-3 px-0 pt-0 border-0 bg-white">
                <div>
                    <div class="fw-semibold">{{ $student['student_name'] }} ({{ $admission }})</div>
                    @if($student['is_missing'])
                        <div class="text-danger small">Student not found in system. Map to proceed.</div>
                    @endif
                    @if($student['has_draft'])
                        <div class="text-warning small">Draft lines present. Set to Sure before approval.</div>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('finance.legacy-imports.approve-student', $batch) }}" method="POST" class="row g-2 align-items-center">
                        @csrf
                        <input type="hidden" name="admission_number" value="{{ $admission }}">
                        <div class="col-12 col-lg-8 position-relative">
                            <input type="hidden" name="student_id" class="student-id-input" value="{{ $student['student_id'] }}">
                            <input type="text" class="form-control form-control-sm student-search-input" placeholder="Search student by name or admission" autocomplete="off" data-search-url="{{ route('finance.legacy-imports.student-search') }}">
                            <div class="student-search-results list-group shadow-sm position-absolute w-100 d-none" style="z-index: 1050; max-height: 220px; overflow-y: auto;"></div>
                        </div>
                        <div class="col-12 col-lg-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                                <i class="bi bi-check2-circle"></i> Approve Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($student['terms'] as $termData)
                        @php $term = $termData['model']; @endphp
                        <div class="col-12">
                            <div class="finance-card shadow-sm border-0 mb-3 p-3">
                                <div class="card-header d-flex justify-content-between align-items-center px-0 pt-0 border-0 bg-white">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold">{{ $term->academic_year }} - {{ $term->term_name }}</span>
                                        <span class="badge {{ $termData['hasDraft'] ? 'bg-warning text-dark' : 'bg-success-subtle text-success' }}">
                                            {{ $termData['hasDraft'] ? 'Draft' : 'Imported' }}
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        Class: {{ $term->class_label ?? '—' }}
                                    </div>
                                </div>
                                <div class="card-body px-0 pb-0">
                                    <div class="table-responsive">
                                        <table class="table table-modern table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Date</th>
                                                    <th>Narration</th>
                                                    <th class="text-end">Dr</th>
                                                    <th class="text-end">Cr</th>
                                                    <th class="text-end">Run Bal</th>
                                                    <th class="text-end">Status</th>
                                                    <th class="text-end">Save</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($termData['lines'] as $line)
                                                    <form action="{{ route('finance.legacy-imports.lines.update', $line) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <tr class="{{ $line->confidence === 'draft' ? 'table-warning' : '' }}">
                                                            <td>{{ $line->sequence_no }}</td>
                                                            <td style="min-width: 140px;">
                                                                <input type="date" name="txn_date" class="form-control form-control-sm" value="{{ $line->txn_date?->toDateString() }}">
                                                            </td>
                                                            <td style="min-width: 480px;">
                                                                <input type="text" name="narration_raw" class="form-control form-control-sm" value="{{ $line->narration_raw }}">
                                                            </td>
                                                            <td style="min-width: 120px;">
                                                                <input type="number" step="0.01" name="amount_dr" class="form-control form-control-sm text-end" placeholder="Dr" value="{{ $line->amount_dr }}">
                                                            </td>
                                                            <td style="min-width: 120px;">
                                                                <input type="number" step="0.01" name="amount_cr" class="form-control form-control-sm text-end" placeholder="Cr" value="{{ $line->amount_cr }}">
                                                            </td>
                                                            <td style="min-width: 140px;">
                                                                <input type="number" step="0.01" name="running_balance" class="form-control form-control-sm text-end" placeholder="Bal" value="{{ $line->running_balance }}">
                                                            </td>
                                                            <td class="text-end" style="min-width: 120px;">
                                                                <span class="badge {{ $line->confidence === 'draft' ? 'bg-warning text-dark' : 'bg-success-subtle text-success' }}">
                                                                    {{ ucfirst($line->confidence ?? 'draft') }}
                                                                </span>
                                                            </td>
                                                            <td class="text-end" style="min-width: 120px;">
                                                                <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                                                                    <i class="bi bi-save"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </form>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted py-3">No lines found.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @php
                        $studentLines = $student['terms']->flatMap(fn($t) => $t['lines']);
                        $studentTotalDr = $studentLines->sum('amount_dr');
                        $studentTotalCr = $studentLines->sum('amount_cr');
                        $studentBalance = $studentTotalDr - $studentTotalCr;
                        $studentDrafts = $studentLines->where('confidence', 'draft')->count();
                    @endphp
                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
                            <div>
                                <strong>Student Totals:</strong>
                                Dr: {{ number_format($studentTotalDr, 2) }},
                                Cr: {{ number_format($studentTotalCr, 2) }},
                                Balance: {{ number_format($studentBalance, 2) }}
                                @if($studentDrafts > 0)
                                    <span class="badge bg-warning text-dark ms-2">{{ $studentDrafts }} draft line(s)</span>
                                @else
                                    <span class="badge bg-success-subtle text-success ms-2">All sure</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="finance-empty-state">
            <div class="finance-empty-state-icon">📄</div>
            <div class="text-muted">No parsed students found.</div>
        </div>
    @endforelse
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modeSelect = document.getElementById('modeSelect');
    const existingBlock = document.getElementById('existingBlock');
    const newBlock = document.getElementById('newBlock');
    if (modeSelect) {
        const toggleBlocks = () => {
            const mode = modeSelect.value;
            if (mode === 'existing') {
                existingBlock.style.display = '';
                newBlock.style.display = 'none';
            } else {
                existingBlock.style.display = 'none';
                newBlock.style.display = '';
            }
        };
        modeSelect.addEventListener('change', toggleBlocks);
        toggleBlocks();
    }

    const containers = document.querySelectorAll('.student-search-input');
    const debounce = (fn, delay = 200) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    };

    containers.forEach(input => {
        const wrapper = input.closest('.position-relative');
        const list = wrapper.querySelector('.student-search-results');
        const hiddenId = wrapper.querySelector('.student-id-input');
        const searchUrl = input.dataset.searchUrl;

        const renderResults = (items) => {
            list.innerHTML = '';
            if (!items.length) {
                list.classList.add('d-none');
                return;
            }
            items.forEach(item => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action py-2';
                a.textContent = item.label;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    input.value = item.label;
                    hiddenId.value = item.id;
                    list.classList.add('d-none');
                });
                list.appendChild(a);
            });
            list.classList.remove('d-none');
        };

        const search = debounce(() => {
            const q = input.value.trim();
            hiddenId.value = '';
            if (q.length < 2) {
                list.classList.add('d-none');
                return;
            }
            fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }})
                .then(res => res.json())
                .then(renderResults)
                .catch(() => list.classList.add('d-none'));
        }, 250);

        input.addEventListener('input', search);
        input.addEventListener('focus', () => {
            if (list.children.length) list.classList.remove('d-none');
        });
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                list.classList.add('d-none');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
    .legacy-card {
        padding: 1.25rem;
    }
</style>
@endpush

