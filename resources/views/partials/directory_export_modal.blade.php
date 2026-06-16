@php
    $modalId = $exportType . 'ExportModal';
    $defaultFields = $defaultFields ?? [];
@endphp

<div class="modal fade directory-export-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title mb-0" id="{{ $modalId }}Label">
                        <i class="bi bi-download"></i> Export {{ ucfirst($exportType) }}
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Pick format, classes, and fields. List filters still apply.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="GET" action="{{ $exportRoute }}" target="_blank" class="directory-export-form">
                <div class="modal-body">
                    @foreach($filterParams ?? [] as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @elseif($value !== null && $value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="settings-card mb-3">
                        <div class="card-body py-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Format</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format" id="{{ $modalId }}_excel" value="excel" checked>
                                            <label class="form-check-label" for="{{ $modalId }}_excel">Excel (.xlsx)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format" id="{{ $modalId }}_pdf" value="pdf">
                                            <label class="form-check-label" for="{{ $modalId }}_pdf">PDF</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7 d-flex flex-wrap justify-content-md-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary directory-export-select-all" data-modal="{{ $modalId }}">
                                        <i class="bi bi-check-all"></i> Select all fields
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary directory-export-clear-all" data-modal="{{ $modalId }}">
                                        Clear fields
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary directory-export-reset-default" data-modal="{{ $modalId }}">
                                        Defaults
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(($exportType ?? '') === 'students' && !empty($classrooms))
                        @php
                            $selectedClassroomIds = array_values(array_unique(array_filter(array_map('intval', (array) ($selectedClassroomIds ?? request()->input('classroom_ids', []))))));
                            if (empty($selectedClassroomIds) && filled(request('classroom_id'))) {
                                $selectedClassroomIds = [(int) request('classroom_id')];
                            }
                        @endphp
                        <div class="settings-card mb-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0">Classes to export</h6>
                                    <small class="text-muted">Leave empty to include all classes</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary directory-export-class-select-all" data-modal="{{ $modalId }}">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary directory-export-class-clear-all" data-modal="{{ $modalId }}">None</button>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control directory-export-class-filter" placeholder="Search classes...">
                                </div>
                                <div class="directory-export-class-list">
                                    @foreach($classrooms as $c)
                                        <label class="directory-export-class-item list-group-item list-group-item-action d-flex align-items-center gap-2 py-2"
                                               data-label="{{ strtolower($c->name) }}">
                                            <input class="form-check-input flex-shrink-0 directory-export-class-checkbox"
                                                   type="checkbox"
                                                   name="classroom_ids[]"
                                                   value="{{ $c->id }}"
                                                   id="{{ $modalId }}_class_{{ $c->id }}"
                                                   @checked(in_array((int) $c->id, $selectedClassroomIds, true))>
                                            <span class="fw-medium">{{ $c->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="settings-card">
                        <div class="card-header py-2">
                            <h6 class="mb-0">Fields to include</h6>
                        </div>
                        <div class="card-body py-2">
                            <div class="input-group input-group-sm mb-3">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control directory-export-field-filter" placeholder="Search fields (e.g. DOB, father, phone)">
                            </div>

                            <div class="directory-export-fields-panel">
                                @foreach($fieldGroups as $group => $fields)
                                    <div class="directory-export-group mb-3">
                                        <div class="fw-semibold small text-uppercase text-muted mb-2">{{ $group }}</div>
                                        <div class="row g-2">
                                            @foreach($fields as $key => $label)
                                                <div class="col-md-4 col-sm-6 directory-export-field-item" data-label="{{ strtolower($label) }}" data-key="{{ strtolower($key) }}">
                                                    <div class="form-check directory-export-field-check">
                                                        <input
                                                            class="form-check-input directory-export-field"
                                                            type="checkbox"
                                                            name="fields[]"
                                                            value="{{ $key }}"
                                                            id="{{ $modalId }}_field_{{ $key }}"
                                                            data-default="{{ in_array($key, $defaultFields, true) ? '1' : '0' }}"
                                                            @checked(in_array($key, $defaultFields, true))
                                                        >
                                                        <label class="form-check-label" for="{{ $modalId }}_field_{{ $key }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-settings-primary">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .directory-export-modal .modal-content {
                max-height: calc(100vh - 2rem);
            }

            .directory-export-modal .directory-export-form {
                display: flex;
                flex-direction: column;
                min-height: 0;
                flex: 1 1 auto;
            }

            .directory-export-modal .modal-body {
                overflow-y: auto;
                max-height: calc(100vh - 11rem);
            }

            .directory-export-class-list,
            .directory-export-fields-panel {
                max-height: 220px;
                overflow-y: auto;
                border: 1px solid var(--bs-border-color, #dee2e6);
                border-radius: 0.5rem;
                background: #fff;
            }

            .directory-export-class-list {
                max-height: 180px;
            }

            .directory-export-class-item {
                border: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                margin: 0;
                cursor: pointer;
            }

            .directory-export-class-item:last-child {
                border-bottom: 0;
            }

            .directory-export-class-item:hover {
                background: rgba(13, 110, 253, 0.06);
            }

            .directory-export-fields-panel {
                padding: 0.75rem;
            }

            .directory-export-field-check {
                padding: 0.35rem 0.5rem;
                border-radius: 0.375rem;
            }

            .directory-export-field-check:hover {
                background: rgba(0, 0, 0, 0.03);
            }
        </style>
    @endpush

    @push('scripts')
    <script>
        function directoryExportModal(el) {
            return el?.closest('.directory-export-modal') || (el?.dataset?.modal ? document.getElementById(el.dataset.modal) : null);
        }

        document.addEventListener('click', function (event) {
            const selectAllBtn = event.target.closest('.directory-export-select-all');
            if (selectAllBtn) {
                directoryExportModal(selectAllBtn)?.querySelectorAll('.directory-export-field').forEach(cb => cb.checked = true);
                return;
            }

            const clearAllBtn = event.target.closest('.directory-export-clear-all');
            if (clearAllBtn) {
                directoryExportModal(clearAllBtn)?.querySelectorAll('.directory-export-field').forEach(cb => cb.checked = false);
                return;
            }

            const resetBtn = event.target.closest('.directory-export-reset-default');
            if (resetBtn) {
                directoryExportModal(resetBtn)?.querySelectorAll('.directory-export-field').forEach(cb => {
                    cb.checked = cb.dataset.default === '1';
                });
                return;
            }

            const classSelectAllBtn = event.target.closest('.directory-export-class-select-all');
            if (classSelectAllBtn) {
                directoryExportModal(classSelectAllBtn)?.querySelectorAll('.directory-export-class-checkbox:not(:disabled)').forEach(cb => {
                    if (!cb.closest('.directory-export-class-item')?.classList.contains('d-none')) {
                        cb.checked = true;
                    }
                });
                return;
            }

            const classClearAllBtn = event.target.closest('.directory-export-class-clear-all');
            if (classClearAllBtn) {
                directoryExportModal(classClearAllBtn)?.querySelectorAll('.directory-export-class-checkbox').forEach(cb => cb.checked = false);
            }
        });

        document.addEventListener('input', function (event) {
            const fieldInput = event.target.closest('.directory-export-field-filter');
            if (fieldInput) {
                const modal = fieldInput.closest('.directory-export-modal');
                const q = (fieldInput.value || '').trim().toLowerCase();

                modal?.querySelectorAll('.directory-export-field-item').forEach(el => {
                    const hay = (el.dataset.label || '') + ' ' + (el.dataset.key || '');
                    el.classList.toggle('d-none', q.length > 0 && !hay.includes(q));
                });

                modal?.querySelectorAll('.directory-export-group').forEach(group => {
                    const hasVisible = !!group.querySelector('.directory-export-field-item:not(.d-none)');
                    group.classList.toggle('d-none', !hasVisible);
                });
                return;
            }

            const classInput = event.target.closest('.directory-export-class-filter');
            if (classInput) {
                const modal = classInput.closest('.directory-export-modal');
                const q = (classInput.value || '').trim().toLowerCase();

                modal?.querySelectorAll('.directory-export-class-item').forEach(el => {
                    const label = el.dataset.label || '';
                    el.classList.toggle('d-none', q.length > 0 && !label.includes(q));
                });
            }
        });
    </script>
    @endpush
@endonce
