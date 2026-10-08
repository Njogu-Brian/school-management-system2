@php
    $feeNotesStudentId = $studentId ?? null;
    $feeNotesFamilyId = $familyId ?? null;
    $feeNotesTitle = $title ?? 'Fee notes';
    $feeNotesScope = $scope ?? ($feeNotesStudentId ? 'student' : 'family');
    $feeNotesCount = isset($noteCount) ? (int) $noteCount : null;
    if ($feeNotesCount === null && ($feeNotesStudentId || $feeNotesFamilyId)) {
        if (($feeNotesScope ?? 'student') === 'family' && $feeNotesFamilyId) {
            $childIds = \App\Models\Student::query()->where('family_id', $feeNotesFamilyId)->pluck('id');
            $feeNotesCount = \App\Models\FinancialNote::query()
                ->where(function ($q) use ($feeNotesFamilyId, $childIds) {
                    $q->where('family_id', $feeNotesFamilyId);
                    if ($childIds->isNotEmpty()) {
                        $q->orWhereIn('student_id', $childIds);
                    }
                })
                ->count();
        } else {
            $feeNotesCount = \App\Models\FinancialNote::forStudentContext(
                $feeNotesStudentId ? (int) $feeNotesStudentId : null,
                $feeNotesFamilyId ? (int) $feeNotesFamilyId : null
            )->count();
        }
    }
    $feeNotesButtonClass = $buttonClass ?? 'btn btn-sm btn-outline-secondary';
@endphp

@if($feeNotesStudentId || $feeNotesFamilyId)
    <button
        type="button"
        class="{{ $feeNotesButtonClass }} fee-notes-launch js-open-fee-notes"
        data-student-id="{{ $feeNotesStudentId }}"
        data-family-id="{{ $feeNotesFamilyId }}"
        data-title="{{ $feeNotesTitle }}"
        data-scope="{{ $feeNotesScope }}"
    >
        <i class="bi bi-journal-richtext"></i>
        <span>Fee notes</span>
        @if(($feeNotesCount ?? 0) > 0)
            <span class="fee-notes-count">{{ $feeNotesCount }}</span>
        @endif
    </button>
@endif

@once
    @push('scripts')
        <div id="feeNotesSheet" class="fee-notes-sheet" hidden>
            <div class="fee-notes-backdrop js-close-fee-notes"></div>
            <section class="fee-notes-panel" role="dialog" aria-modal="true" aria-labelledby="feeNotesTitle">
                <div class="fee-notes-handle" aria-hidden="true"></div>
                <header class="fee-notes-head">
                    <div>
                        <p class="fee-notes-kicker">Fee notes</p>
                        <h2 id="feeNotesTitle" class="fee-notes-title">Notes</h2>
                    </div>
                    <button type="button" class="fee-notes-close js-close-fee-notes" aria-label="Close fee notes">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>
                <div class="fee-notes-list" id="feeNotesList">
                    <p class="fee-notes-empty">Open a record to load notes.</p>
                </div>
                <form id="feeNotesForm" class="fee-notes-composer">
                    <label class="visually-hidden" for="feeNotesBody">Note</label>
                    <textarea id="feeNotesBody" name="body" rows="2" maxlength="5000" placeholder="Promise, follow-up, or adjustment…" required></textarea>
                    <div class="fee-notes-composer-row">
                        <label class="fee-notes-date">
                            <span>Promise</span>
                            <input type="date" name="promise_date">
                        </label>
                        <label class="fee-notes-pin">
                            <input type="checkbox" name="is_pinned" value="1">
                            Pin
                        </label>
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                    <p class="fee-notes-error" id="feeNotesError" hidden></p>
                </form>
            </section>
        </div>
        <style>
            .fee-notes-launch {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .fee-notes-count {
                display: inline-flex;
                min-width: 1.25rem;
                height: 1.25rem;
                padding: 0 6px;
                border-radius: 999px;
                align-items: center;
                justify-content: center;
                background: #3a1a59;
                color: #fff;
                font-size: 0.72rem;
                font-weight: 700;
            }
            .fee-notes-sheet[hidden] { display: none !important; }
            .fee-notes-sheet {
                position: fixed;
                inset: 0;
                z-index: 2080;
            }
            .fee-notes-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.45);
            }
            .fee-notes-panel {
                position: absolute;
                background: #fffdf8;
                display: flex;
                flex-direction: column;
                box-shadow: 0 18px 50px rgba(15, 23, 42, 0.28);
                max-height: min(92vh, 760px);
            }
            .fee-notes-handle {
                width: 42px;
                height: 4px;
                border-radius: 999px;
                background: #d6d3d1;
                margin: 8px auto 0;
            }
            .fee-notes-head {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: flex-start;
                padding: 10px 16px 8px;
            }
            .fee-notes-kicker {
                margin: 0;
                font-size: 0.72rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #a16207;
                font-weight: 700;
            }
            .fee-notes-title {
                margin: 0;
                font-size: 1.15rem;
                font-weight: 800;
                color: #1c1917;
            }
            .fee-notes-close {
                border: 0;
                background: rgba(0,0,0,0.05);
                width: 36px;
                height: 36px;
                border-radius: 999px;
            }
            .fee-notes-list {
                overflow: auto;
                padding: 4px 16px 12px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                flex: 1;
            }
            .fee-notes-card {
                background: #fff;
                border: 1px solid #f5e6c8;
                border-left: 4px solid #f59e0b;
                border-radius: 12px;
                padding: 10px 12px;
                box-shadow: 0 1px 0 rgba(120, 53, 15, 0.04);
            }
            .fee-notes-card.is-pinned { border-left-color: #3a1a59; }
            .fee-notes-card-top {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                align-items: flex-start;
            }
            .fee-notes-badges { display: flex; flex-wrap: wrap; gap: 4px; }
            .fee-notes-body {
                margin: 8px 0 6px;
                white-space: pre-wrap;
                color: #292524;
            }
            .fee-notes-meta { color: #78716c; font-size: 0.78rem; }
            .fee-notes-empty, .fee-notes-error { color: #78716c; margin: 8px 0; }
            .fee-notes-error { color: #b91c1c; }
            .fee-notes-composer {
                border-top: 1px solid #f5e6c8;
                padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
                background: #fff;
            }
            .fee-notes-composer textarea {
                width: 100%;
                border: 1px solid #e7e5e4;
                border-radius: 10px;
                padding: 8px 10px;
                resize: vertical;
                min-height: 64px;
            }
            .fee-notes-composer-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
                margin-top: 8px;
            }
            .fee-notes-date, .fee-notes-pin {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.82rem;
                margin: 0;
            }
            .fee-notes-date input { max-width: 11rem; }
            .fee-notes-composer-row .btn { margin-left: auto; }
            @media (max-width: 767.98px) {
                .fee-notes-panel {
                    left: 0;
                    right: 0;
                    bottom: 0;
                    border-radius: 18px 18px 0 0;
                    height: min(92vh, 760px);
                }
            }
            @media (min-width: 768px) {
                .fee-notes-handle { display: none; }
                .fee-notes-panel {
                    top: 0;
                    right: 0;
                    bottom: 0;
                    width: min(440px, 100vw);
                    max-height: none;
                    border-radius: 0;
                    height: 100%;
                }
            }
        </style>
        <script>
            (function () {
                const sheet = document.getElementById('feeNotesSheet');
                if (!sheet || sheet.dataset.bound === '1') return;
                sheet.dataset.bound = '1';
                const listEl = document.getElementById('feeNotesList');
                const titleEl = document.getElementById('feeNotesTitle');
                const form = document.getElementById('feeNotesForm');
                const errorEl = document.getElementById('feeNotesError');
                const indexUrl = @json(\Illuminate\Support\Facades\Route::has('finance.financial-notes.index') ? route('finance.financial-notes.index') : url('/finance/financial-notes'));
                const storeUrl = @json(\Illuminate\Support\Facades\Route::has('finance.financial-notes.store') ? route('finance.financial-notes.store') : url('/finance/financial-notes'));
                const destroyBase = @json(url('/finance/financial-notes'));
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                let context = { studentId: '', familyId: '', scope: 'student' };

                function showError(message) {
                    if (!errorEl) return;
                    errorEl.hidden = !message;
                    errorEl.textContent = message || '';
                }

                function renderNotes(notes) {
                    if (!notes || !notes.length) {
                        listEl.innerHTML = '<p class="fee-notes-empty">No fee notes yet. Add the first one below.</p>';
                        return;
                    }
                    listEl.innerHTML = notes.map(function (note) {
                        const badges = [];
                        if (note.is_pinned) badges.push('<span class="badge text-bg-warning">Pinned</span>');
                        badges.push('<span class="badge ' + (note.scope === 'family' ? 'text-bg-info' : 'text-bg-secondary') + '">' + (note.scope === 'family' ? 'Family' : 'Student') + '</span>');
                        if (note.promise_label) badges.push('<span class="badge text-bg-primary">Promise ' + note.promise_label + '</span>');
                        return '<article class="fee-notes-card' + (note.is_pinned ? ' is-pinned' : '') + '">' +
                            '<div class="fee-notes-card-top"><div class="fee-notes-badges">' + badges.join('') + '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-delete-fee-note" data-id="' + note.id + '" aria-label="Delete note"><i class="bi bi-trash"></i></button></div>' +
                            '<div class="fee-notes-body"></div>' +
                            '<div class="fee-notes-meta"></div></article>';
                    }).join('');
                    listEl.querySelectorAll('.fee-notes-card').forEach(function (card, index) {
                        card.querySelector('.fee-notes-body').textContent = notes[index].body || '';
                        card.querySelector('.fee-notes-meta').textContent = (notes[index].author || 'Staff') + ' · ' + (notes[index].created_at || '');
                    });
                }

                function loadNotes() {
                    const params = new URLSearchParams();
                    if (context.scope === 'family') {
                        if (context.familyId) params.set('family_id', context.familyId);
                    } else {
                        if (context.studentId) params.set('student_id', context.studentId);
                        if (context.familyId) params.set('family_id', context.familyId);
                    }
                    listEl.innerHTML = '<p class="fee-notes-empty">Loading notes…</p>';
                    fetch(indexUrl + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (res) {
                        if (!res.ok) throw new Error('Could not load notes.');
                        return res.json();
                    }).then(function (data) {
                        renderNotes(data.notes || []);
                    }).catch(function (err) {
                        listEl.innerHTML = '<p class="fee-notes-error">' + err.message + '</p>';
                    });
                }

                function openSheet(button) {
                    context = {
                        studentId: button.getAttribute('data-student-id') || '',
                        familyId: button.getAttribute('data-family-id') || '',
                        scope: button.getAttribute('data-scope') || 'student'
                    };
                    titleEl.textContent = button.getAttribute('data-title') || 'Fee notes';
                    showError('');
                    form.reset();
                    sheet.hidden = false;
                    document.body.style.overflow = 'hidden';
                    loadNotes();
                }

                function closeSheet() {
                    sheet.hidden = true;
                    document.body.style.overflow = '';
                }

                document.addEventListener('click', function (event) {
                    const openBtn = event.target.closest('.js-open-fee-notes');
                    if (openBtn) {
                        event.preventDefault();
                        openSheet(openBtn);
                        return;
                    }
                    if (event.target.closest('.js-close-fee-notes')) {
                        closeSheet();
                        return;
                    }
                    const deleteBtn = event.target.closest('.js-delete-fee-note');
                    if (deleteBtn && sheet.contains(deleteBtn)) {
                        if (!confirm('Remove this note?')) return;
                        fetch(destroyBase + '/' + deleteBtn.getAttribute('data-id'), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(function (res) {
                            if (!res.ok) throw new Error('Could not remove note.');
                            loadNotes();
                        }).catch(function (err) {
                            showError(err.message);
                        });
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !sheet.hidden) closeSheet();
                });

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    showError('');
                    const payload = new FormData(form);
                    if (context.studentId && context.scope !== 'family') payload.set('student_id', context.studentId);
                    if (context.familyId) payload.set('family_id', context.familyId);
                    if (!payload.get('is_pinned')) payload.set('is_pinned', '0');
                    fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: payload
                    }).then(function (res) {
                        return res.json().then(function (data) {
                            if (!res.ok) throw new Error(data.message || 'Could not save note.');
                            return data;
                        });
                    }).then(function () {
                        form.reset();
                        loadNotes();
                    }).catch(function (err) {
                        showError(err.message);
                    });
                });
            })();
        </script>
    @endpush
@endonce
