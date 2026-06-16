@php
    $modalId = $exportType . 'ExportModal';
    $defaultFields = $defaultFields ?? [];
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $exportRoute }}" target="_blank">
                @csrf
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

                    @foreach($fieldGroups as $group => $fields)
                        <div class="mb-3">
                            <div class="fw-semibold small text-uppercase text-muted mb-2">{{ $group }}</div>
                            <div class="row g-2">
                                @foreach($fields as $key => $label)
                                    <div class="col-md-4 col-sm-6">
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
    </script>
    @endpush
@endonce
