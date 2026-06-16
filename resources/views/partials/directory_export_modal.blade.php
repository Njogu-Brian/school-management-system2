@php
    $modalId = $exportType . 'ExportModal';
    $defaultFields = $defaultFields ?? [];
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="GET" action="{{ $exportRoute }}" target="_blank">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        <i class="bi bi-download"></i> Export {{ ucfirst($exportType) }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Choose the fields to include and export format. Current list filters are applied automatically.
                    </p>

                    @foreach($filterParams ?? [] as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @elseif($value !== null && $value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Format</label>
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
                        <div class="col-md-6 d-flex align-items-end justify-content-md-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary directory-export-select-all" data-modal="{{ $modalId }}">
                                Select all
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary directory-export-clear-all" data-modal="{{ $modalId }}">
                                Clear all
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary directory-export-reset-default" data-modal="{{ $modalId }}">
                                Defaults
                            </button>
                        </div>
                    </div>

                    @if(($exportType ?? '') === 'students' && !empty($classrooms))
                        @php
                            $selectedClassroomIds = array_values(array_unique(array_filter(array_map('intval', (array) ($selectedClassroomIds ?? request()->input('classroom_ids', []))))));
                            if (empty($selectedClassroomIds) && filled(request('classroom_id'))) {
                                $selectedClassroomIds = [(int) request('classroom_id')];
                            }
                        @endphp
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Classes to export</label>
                            <select name="classroom_ids[]" class="form-select directory-export-classroom-select" multiple size="8">
                                @foreach($classrooms as $c)
                                    <option value="{{ $c->id }}" @selected(in_array((int) $c->id, $selectedClassroomIds, true))>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Leave empty to export all classes (or use the list filters).
                            </div>
                        </div>
                    @endif

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Fields</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control directory-export-field-filter" placeholder="Search fields (e.g. DOB, father, phone)">
                        </div>
                    </div>

                    @foreach($fieldGroups as $group => $fields)
                        <div class="mb-3 directory-export-group">
                            <div class="fw-semibold small text-uppercase text-muted mb-2">{{ $group }}</div>
                            <div class="row g-2">
                                @foreach($fields as $key => $label)
                                    <div class="col-md-4 col-sm-6 directory-export-field-item" data-label="{{ strtolower($label) }}" data-key="{{ strtolower($key) }}">
                                        <div class="form-check">
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
                <div class="modal-footer">
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
            .directory-export-classroom-select { font-size: 0.95rem; }
            .directory-export-field-filter { font-size: 0.95rem; }
        </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const selectAllBtn = event.target.closest('.directory-export-select-all');
            if (selectAllBtn) {
                const modal = document.getElementById(selectAllBtn.dataset.modal);
                modal?.querySelectorAll('.directory-export-field').forEach(cb => cb.checked = true);
                return;
            }

            const clearAllBtn = event.target.closest('.directory-export-clear-all');
            if (clearAllBtn) {
                const modal = document.getElementById(clearAllBtn.dataset.modal);
                modal?.querySelectorAll('.directory-export-field').forEach(cb => cb.checked = false);
                return;
            }

            const resetBtn = event.target.closest('.directory-export-reset-default');
            if (resetBtn) {
                const modal = document.getElementById(resetBtn.dataset.modal);
                modal?.querySelectorAll('.directory-export-field').forEach(cb => {
                    cb.checked = cb.dataset.default === '1';
                });
            }
        });

        document.addEventListener('input', function (event) {
            const input = event.target.closest('.directory-export-field-filter');
            if (!input) return;

            const modal = input.closest('.modal');
            const q = (input.value || '').trim().toLowerCase();

            modal?.querySelectorAll('.directory-export-field-item').forEach(el => {
                const hay = (el.dataset.label || '') + ' ' + (el.dataset.key || '');
                el.classList.toggle('d-none', q.length > 0 && !hay.includes(q));
            });

            modal?.querySelectorAll('.directory-export-group').forEach(group => {
                const hasVisible = !!group.querySelector('.directory-export-field-item:not(.d-none)');
                group.classList.toggle('d-none', !hasVisible);
            });
        });
    </script>
    @endpush
@endonce
