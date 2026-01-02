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
            <div class="col-md-4">
                <div class="text-muted small">File</div>
                <div class="fw-semibold text-truncate" style="max-width: 100%;">{{ $batch->file_name }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Class</div>
                <div class="fw-semibold">{{ $batch->class_label ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Students</div>
                <div class="fw-semibold">{{ $batch->total_students }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Drafts</div>
                <span class="text-muted">{{ $batch->draft_students }}</span>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('finance.legacy-imports.edit-history', $batch) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history"></i> View Edit History
                    </a>
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
            </div>
        </div>
    </div>

    {{-- Posting UI removed per request --}}

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
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="text-muted small">Approval/posting was removed; legacy data is displayed as-is.</span>
                    @if($student['student_id'])
                        <span class="badge bg-success">Mapped to ID {{ $student['student_id'] }}</span>
                    @else
                        <span class="badge bg-warning text-dark">Not mapped to a system student</span>
                    @endif
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
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($termData['lines'] as $line)
                                                    <tr class="{{ $line->confidence === 'draft' ? 'table-warning' : '' }}" data-line-id="{{ $line->id }}">
                                                        <td>{{ $line->sequence_no }}</td>
                                                        <td style="min-width: 140px;">
                                                            <input type="date" 
                                                                   class="form-control form-control-sm editable-field" 
                                                                   value="{{ $line->txn_date?->toDateString() }}" 
                                                                   data-field="txn_date"
                                                                   data-line-id="{{ $line->id }}">
                                                        </td>
                                                        <td style="min-width: 480px;">
                                                            <input type="text" 
                                                                   class="form-control form-control-sm editable-field" 
                                                                   value="{{ $line->narration_raw }}" 
                                                                   data-field="narration_raw"
                                                                   data-line-id="{{ $line->id }}">
                                                        </td>
                                                        <td class="text-end" style="min-width: 120px;">
                                                            <input type="number" 
                                                                   step="0.01" 
                                                                   class="form-control form-control-sm editable-field text-end" 
                                                                   value="{{ $line->amount_dr ?? '' }}" 
                                                                   data-field="amount_dr"
                                                                   data-line-id="{{ $line->id }}"
                                                                   placeholder="0.00">
                                                        </td>
                                                        <td class="text-end" style="min-width: 120px;">
                                                            <input type="number" 
                                                                   step="0.01" 
                                                                   class="form-control form-control-sm editable-field text-end" 
                                                                   value="{{ $line->amount_cr ?? '' }}" 
                                                                   data-field="amount_cr"
                                                                   data-line-id="{{ $line->id }}"
                                                                   placeholder="0.00">
                                                        </td>
                                                        <td class="text-end running-balance" style="min-width: 140px;" data-line-id="{{ $line->id }}">
                                                            {{ number_format($line->running_balance ?? 0, 2) }}
                                                        </td>
                                                        <td class="text-end" style="min-width: 120px;">
                                                            <span class="badge {{ $line->confidence === 'draft' ? 'bg-warning text-dark' : 'bg-success-subtle text-success' }}">
                                                                {{ ucfirst($line->confidence ?? 'draft') }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end" style="min-width: 100px;">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary save-line-btn" 
                                                                    data-line-id="{{ $line->id }}"
                                                                    style="display: none;">
                                                                <i class="bi bi-save"></i> Save
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted py-3">No lines found.</td>
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
                        // Get the last term's ending balance (current balance)
                        $lastTerm = $student['terms']->last();
                        $currentBalance = $lastTerm ? ($lastTerm['lines']->last()->running_balance ?? 0) : 0;
                    @endphp
                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
                            <div>
                                <strong>Student Totals:</strong>
                                Dr: <span class="student-total-dr">{{ number_format($studentTotalDr, 2) }}</span>,
                                Cr: <span class="student-total-cr">{{ number_format($studentTotalCr, 2) }}</span>,
                                Balance: <span class="student-balance">{{ number_format($studentBalance, 2) }}</span>
                                @if($studentDrafts > 0)
                                    <span class="badge bg-warning text-dark ms-2">{{ $studentDrafts }} draft line(s)</span>
                                @else
                                    <span class="badge bg-success-subtle text-success ms-2">All sure</span>
                                @endif
                            </div>
                            <div>
                                <strong>Current Balance:</strong> <span class="student-current-balance">{{ number_format($currentBalance, 2) }}</span>
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
    // Legacy transaction inline editing
    const editableFields = document.querySelectorAll('.editable-field');
    const saveButtons = document.querySelectorAll('.save-line-btn');
    let saveTimeout;
    let pendingSaves = new Set(); // Track which lines have unsaved changes
    
    // Show save button when field is edited
    editableFields.forEach(field => {
        const originalValue = field.value;
        
        field.addEventListener('input', function() {
            const lineId = this.dataset.lineId;
            const row = this.closest('tr');
            const saveBtn = row.querySelector('.save-line-btn');
            
            // Show save button if value changed
            if (this.value !== originalValue) {
                pendingSaves.add(lineId);
                if (saveBtn) {
                    saveBtn.style.display = 'inline-block';
                }
            } else {
                // Check if all fields in row are back to original
                const allFields = row.querySelectorAll('.editable-field');
                let hasChanges = false;
                allFields.forEach(f => {
                    if (f.value !== f.defaultValue) {
                        hasChanges = true;
                    }
                });
                if (!hasChanges) {
                    pendingSaves.delete(lineId);
                    if (saveBtn) {
                        saveBtn.style.display = 'none';
                    }
                }
            }
            
            // Prevent both dr and cr from being set
            if (this.dataset.field === 'amount_dr' || this.dataset.field === 'amount_cr') {
                const drField = row.querySelector('[data-field="amount_dr"]');
                const crField = row.querySelector('[data-field="amount_cr"]');
                
                if (this.dataset.field === 'amount_dr' && this.value && crField.value) {
                    crField.value = '';
                } else if (this.dataset.field === 'amount_cr' && this.value && drField.value) {
                    drField.value = '';
                }
            }
        });
        
        // Store original value
        field.defaultValue = field.value;
    });
    
    // Handle save button clicks
    saveButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lineId = this.dataset.lineId;
            const row = document.querySelector(`tr[data-line-id="${lineId}"]`);
            
            if (row) {
                const txnDate = row.querySelector('[data-field="txn_date"]').value;
                const narration = row.querySelector('[data-field="narration_raw"]').value;
                const amountDr = row.querySelector('[data-field="amount_dr"]').value;
                const amountCr = row.querySelector('[data-field="amount_cr"]').value;
                
                saveLineField(lineId, null, null, {
                    txn_date: txnDate || '',
                    narration_raw: narration,
                    amount_dr: amountDr || '',
                    amount_cr: amountCr || '',
                    confidence: 'high'
                });
            }
        });
    });
    
    function saveLineField(lineId, fieldName, value, allFields = null) {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        
        // Get all current field values from the row
        const row = document.querySelector(`tr[data-line-id="${lineId}"]`);
        const saveBtn = row ? row.querySelector('.save-line-btn') : null;
        
        if (allFields) {
            // Manual save with all fields
            Object.keys(allFields).forEach(key => {
                formData.append(key, allFields[key]);
            });
        } else if (row) {
            // Auto-save with single field change
            if (fieldName) {
                formData.append(fieldName, value);
            }
            const txnDate = row.querySelector('[data-field="txn_date"]').value;
            const narration = row.querySelector('[data-field="narration_raw"]').value;
            const amountDr = row.querySelector('[data-field="amount_dr"]').value;
            const amountCr = row.querySelector('[data-field="amount_cr"]').value;
            
            formData.append('txn_date', txnDate || '');
            formData.append('narration_raw', narration);
            formData.append('amount_dr', amountDr || '');
            formData.append('amount_cr', amountCr || '');
            formData.append('confidence', 'high');
        }
        
        // Disable save button and show loading
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        }
        
        // Construct URL using base path and line ID
        const baseUrl = '{{ url("/") }}';
        const updateUrl = `${baseUrl}/finance/legacy-imports/lines/${lineId}`;
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update running balances in the UI
                updateRunningBalances(lineId);
                // Show success message
                showNotification('Transaction updated successfully', 'success');
                
                // Hide save button and update original values
                if (row) {
                    pendingSaves.delete(lineId);
                    if (saveBtn) {
                        saveBtn.style.display = 'none';
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
                    }
                    // Update default values
                    row.querySelectorAll('.editable-field').forEach(field => {
                        field.defaultValue = field.value;
                    });
                }
            } else {
                showNotification(data.message || 'Error updating transaction', 'error');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating transaction', 'error');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
            }
        });
    }
    
    function updateRunningBalances(editedLineId) {
        // Reload the page to show updated running balances
        // Alternatively, we could fetch updated data via AJAX
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
    
    function showNotification(message, type) {
        // Simple notification - you can enhance this
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
    
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

