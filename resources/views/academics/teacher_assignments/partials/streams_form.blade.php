@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .teacher-streams-form .stream-select-tags {
            display: flex; flex-wrap: wrap; gap: 0.35rem; min-height: 2.25rem;
            padding: 0.35rem 0.5rem; border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem; background: #fff; cursor: pointer;
        }
        .teacher-streams-form .stream-tag {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.15rem 0.5rem; border-radius: 999px;
            background: rgba(111, 66, 193, 0.12); color: #5a32a3; font-size: 0.85rem;
        }
        .teacher-streams-form .stream-tag button {
            border: none; background: none; padding: 0; line-height: 1;
            color: inherit; opacity: 0.7;
        }
        .teacher-streams-form .stream-dropdown {
            position: absolute; z-index: 20; width: 100%; max-height: 220px;
            overflow-y: auto; background: #fff; border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem; box-shadow: 0 4px 12px rgba(0,0,0,.08);
            display: none;
        }
        .teacher-streams-form .stream-dropdown.show { display: block; }
        .teacher-streams-form .stream-option {
            padding: 0.5rem 0.75rem; cursor: pointer;
        }
        .teacher-streams-form .stream-option:hover,
        .teacher-streams-form .stream-option.selected { background: rgba(111, 66, 193, 0.08); }
        .teacher-streams-form .stream-card {
            border: 1px solid var(--bs-border-color); border-radius: 0.5rem;
            padding: 1rem; margin-bottom: 1rem; background: #faf9fc;
        }
        .teacher-streams-form .subject-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.5rem;
        }
        .teacher-streams-form .subject-chip {
            display: flex; align-items: center; gap: 0.35rem;
            padding: 0.45rem 0.6rem; border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem; background: #fff; cursor: pointer; font-size: 0.875rem;
            margin: 0; user-select: none;
        }
        .teacher-streams-form .subject-chip:has(input:checked) {
            background: rgba(111, 66, 193, 0.15); border-color: #6f42c1;
        }
        .teacher-streams-form .subject-chip input { accent-color: #6f42c1; }
        .teacher-streams-form .role-toggles { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
        .teacher-streams-form .stream-select-wrap { position: relative; }
    </style>
@endpush

@php
    $formAction = $formAction ?? route('academics.teacher-assignments.update', $staff->id);
    $redirectTo = $redirectTo ?? null;
    $selectedSlotKeys = $selectedSlotKeys ?? [];
    $slotData = $slotData ?? [];
    $subjectsBySlot = $subjectsBySlot ?? [];
    $streamSlotsJson = $streamSlots->map(fn ($s) => [
        'key' => $s->key,
        'classroom_id' => $s->classroom_id,
        'stream_id' => $s->stream_id,
        'label' => $s->label,
        'subjects' => $subjectsBySlot[$s->key] ?? [],
    ])->values();
@endphp

<div class="teacher-streams-form" id="teacherStreamsForm"
     data-stream-slots='@json($streamSlotsJson)'
     data-initial-slots='@json($slotData)'>
    <div class="mb-3">
        <label class="form-label fw-semibold">Streams to teach <span class="text-danger">*</span></label>
        <div class="stream-select-wrap">
            <div class="stream-select-tags" id="streamTagsArea" tabindex="0" role="button" aria-label="Select streams">
                <span class="text-muted small" id="streamPlaceholder">Select streams to assign the teacher</span>
            </div>
            <div class="stream-dropdown" id="streamDropdown"></div>
        </div>
        <small class="text-muted">Select the learning areas the teacher will handle for each stream.</small>
    </div>

    <div id="streamCardsContainer"></div>

    <div id="streamFormFields"></div>

    @if($redirectTo)
        <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
    @endif
</div>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('teacherStreamsForm');
    if (!root) return;

    const streamSlots = JSON.parse(root.dataset.streamSlots || '[]');
    const initialSlots = JSON.parse(root.dataset.initialSlots || '{}');
    const selected = new Set(Object.keys(initialSlots));

    const tagsArea = document.getElementById('streamTagsArea');
    const dropdown = document.getElementById('streamDropdown');
    const placeholder = document.getElementById('streamPlaceholder');
    const cardsContainer = document.getElementById('streamCardsContainer');
    const formFields = document.getElementById('streamFormFields');

    function slotByKey(key) {
        return streamSlots.find(s => s.key === key);
    }

    function renderDropdown() {
        dropdown.innerHTML = streamSlots.map(s => {
            const sel = selected.has(s.key) ? ' selected' : '';
            return `<div class="stream-option${sel}" data-key="${s.key}">${s.label}</div>`;
        }).join('');
    }

    function renderTags() {
        const keys = [...selected];
        if (!keys.length) {
            placeholder.style.display = '';
            tagsArea.querySelectorAll('.stream-tag').forEach(el => el.remove());
            return;
        }
        placeholder.style.display = 'none';
        tagsArea.querySelectorAll('.stream-tag').forEach(el => el.remove());
        keys.forEach(key => {
            const slot = slotByKey(key);
            if (!slot) return;
            const tag = document.createElement('span');
            tag.className = 'stream-tag';
            tag.innerHTML = `${slot.label} <button type="button" data-remove="${key}" aria-label="Remove">&times;</button>`;
            tagsArea.appendChild(tag);
        });
    }

    function renderCards() {
        cardsContainer.innerHTML = '';
        formFields.innerHTML = '';
        let idx = 0;

        [...selected].forEach(key => {
            const slot = slotByKey(key);
            if (!slot) return;
            const init = initialSlots[key] || {};
            const subjects = slot.subjects || [];
            const isClassTeacher = !!init.is_class_teacher;
            const isAssistant = !!init.is_assistant_teacher;
            const subjectIds = init.subject_ids || [];

            const card = document.createElement('div');
            card.className = 'stream-card';
            card.dataset.key = key;
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 fw-semibold">${slot.label}</h6>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-card="${key}" title="Remove stream">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="role-toggles">
                    <div class="form-check">
                        <input class="form-check-input role-class-teacher" type="checkbox"
                               id="ct_${key}" data-key="${key}" ${isClassTeacher ? 'checked' : ''}>
                        <label class="form-check-label" for="ct_${key}">Class Teacher</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input role-assistant" type="checkbox"
                               id="at_${key}" data-key="${key}" ${isAssistant ? 'checked' : ''}>
                        <label class="form-check-label" for="at_${key}">Assistant Teacher</label>
                    </div>
                </div>
                <p class="small text-muted mb-2">Learning areas for this stream</p>
                <div class="subject-grid" data-subjects-for="${key}"></div>
            `;
            cardsContainer.appendChild(card);

            const grid = card.querySelector(`[data-subjects-for="${key}"]`);
            if (!subjects.length) {
                grid.innerHTML = '<p class="text-muted small mb-0">No subjects configured for this stream. Assign subjects to the classroom first.</p>';
            } else {
                subjects.forEach(sub => {
                    const checked = subjectIds.includes(sub.subject_id) ? 'checked' : '';
                    const label = document.createElement('label');
                    label.className = 'subject-chip';
                    label.innerHTML = `<input type="checkbox" class="subject-check" data-key="${key}" data-subject-id="${sub.subject_id}" ${checked}> ${sub.name}`;
                    grid.appendChild(label);
                });
            }

            const streamIdVal = slot.stream_id === null ? '' : slot.stream_id;
            formFields.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="slots[${idx}][classroom_id]" value="${slot.classroom_id}" data-field-key="${key}" data-field="classroom_id">
                <input type="hidden" name="slots[${idx}][stream_id]" value="${streamIdVal}" data-field-key="${key}" data-field="stream_id">
                <input type="hidden" name="slots[${idx}][is_class_teacher]" value="${isClassTeacher ? 1 : 0}" data-field-key="${key}" data-field="is_class_teacher">
                <input type="hidden" name="slots[${idx}][is_assistant_teacher]" value="${isAssistant ? 1 : 0}" data-field-key="${key}" data-field="is_assistant_teacher">
            `);
            subjectIds.forEach(sid => {
                formFields.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="slots[${idx}][subject_ids][]" value="${sid}" data-field-key="${key}" data-field="subject_id" data-subject-id="${sid}">
                `);
            });
            idx++;
        });
    }

    function syncHiddenFields() {
        formFields.innerHTML = '';
        let idx = 0;
        [...selected].forEach(key => {
            const slot = slotByKey(key);
            if (!slot) return;
            const card = cardsContainer.querySelector(`[data-key="${key}"]`);
            const isCT = card?.querySelector('.role-class-teacher')?.checked;
            const isAT = card?.querySelector('.role-assistant')?.checked;
            const checkedSubjects = card ? [...card.querySelectorAll('.subject-check:checked')].map(el => el.dataset.subjectId) : [];

            const streamIdVal = slot.stream_id === null ? '' : slot.stream_id;
            formFields.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="slots[${idx}][classroom_id]" value="${slot.classroom_id}">
                <input type="hidden" name="slots[${idx}][stream_id]" value="${streamIdVal}">
                <input type="hidden" name="slots[${idx}][is_class_teacher]" value="${isCT ? 1 : 0}">
                <input type="hidden" name="slots[${idx}][is_assistant_teacher]" value="${isAT ? 1 : 0}">
            `);
            checkedSubjects.forEach(sid => {
                formFields.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="slots[${idx}][subject_ids][]" value="${sid}">
                `);
            });
            idx++;
        });
    }

    tagsArea.addEventListener('click', (e) => {
        if (e.target.closest('[data-remove]')) return;
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) dropdown.classList.remove('show');
    });

    dropdown.addEventListener('click', (e) => {
        const opt = e.target.closest('.stream-option');
        if (!opt) return;
        const key = opt.dataset.key;
        if (selected.has(key)) selected.delete(key);
        else selected.add(key);
        renderDropdown();
        renderTags();
        renderCards();
    });

    tagsArea.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove]');
        if (!btn) return;
        e.stopPropagation();
        selected.delete(btn.dataset.remove);
        renderDropdown();
        renderTags();
        renderCards();
    });

    cardsContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-card]');
        if (!btn) return;
        selected.delete(btn.dataset.removeCard);
        renderDropdown();
        renderTags();
        renderCards();
    });

    cardsContainer.addEventListener('change', () => syncHiddenFields());

    const parentForm = root.closest('form');
    if (parentForm) {
        parentForm.addEventListener('submit', () => syncHiddenFields());
    }

    renderDropdown();
    renderTags();
    renderCards();
})();
</script>
@endpush
